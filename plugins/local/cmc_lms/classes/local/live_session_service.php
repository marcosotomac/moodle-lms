<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms\local;

use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filelib.php');

/**
 * Creates live sessions with external video providers.
 *
 * This service deliberately uses Moodle core curl instead of vendor SDKs so the
 * local plugin remains portable and does not require Composer dependencies.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class live_session_service {
    /** @var string Zoom provider key. */
    private const PROVIDER_ZOOM = 'zoom';

    /** @var string Google Meet provider key. */
    private const PROVIDER_MEET = 'meet';

    /** @var string Google OAuth token endpoint. */
    private const GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';

    /** @var string Google Meet space creation endpoint. */
    private const GOOGLE_MEET_SPACES_URL = 'https://meet.googleapis.com/v2/spaces';

    /** @var string Zoom OAuth token endpoint. */
    private const ZOOM_TOKEN_URL = 'https://zoom.us/oauth/token';

    /**
     * Create a live session for a program-course mapping.
     *
     * @param stdClass $program CMC program record.
     * @param stdClass $course Moodle course record.
     * @param stdClass $metadata Submitted program-course metadata.
     * @return stdClass Result with provider, externalid and joinurl.
     */
    public function create_session(stdClass $program, stdClass $course, stdClass $metadata): stdClass {
        $provider = strtolower(trim($metadata->liveprovider ?? ''));
        if ($provider === self::PROVIDER_ZOOM) {
            return $this->create_zoom_meeting($program, $course, $metadata);
        }
        if ($provider === self::PROVIDER_MEET) {
            return $this->create_google_meet_space($program, $course, $metadata);
        }

        throw new moodle_exception('invalidliveintegrationprovider', 'local_cmc_lms');
    }

    /**
     * Create a scheduled Zoom meeting using Server-to-Server OAuth.
     *
     * @param stdClass $program CMC program record.
     * @param stdClass $course Moodle course record.
     * @param stdClass $metadata Submitted program-course metadata.
     * @return stdClass
     */
    private function create_zoom_meeting(stdClass $program, stdClass $course, stdClass $metadata): stdClass {
        if (!get_config('local_cmc_lms', 'zoom_enabled')) {
            throw new moodle_exception('liveintegrationdisabled', 'local_cmc_lms', '', 'Zoom');
        }

        $token = $this->get_zoom_access_token();
        $userid = $this->required_config('zoom_userid');
        $duration = max(1, (int)ceil(((int)$metadata->scheduleend - (int)$metadata->schedulestart) / MINSECS));
        $topic = $this->session_topic($program, $course, $metadata);

        $payload = [
            'topic' => $topic,
            'type' => 2,
            'start_time' => gmdate('Y-m-d\TH:i:s\Z', (int)$metadata->schedulestart),
            'duration' => $duration,
            'timezone' => 'UTC',
            'agenda' => $this->session_description($program, $course),
            'settings' => [
                'join_before_host' => false,
                'waiting_room' => true,
            ],
        ];

        $response = $this->post_json(
            'https://api.zoom.us/v2/users/' . rawurlencode($userid) . '/meetings',
            $payload,
            ['Authorization: Bearer ' . $token]
        );

        if (empty($response->join_url) || empty($response->id)) {
            throw new moodle_exception('liveintegrationmissingurl', 'local_cmc_lms', '', 'Zoom');
        }

        return (object)[
            'provider' => self::PROVIDER_ZOOM,
            'externalid' => (string)$response->id,
            'joinurl' => (string)$response->join_url,
        ];
    }

    /**
     * Request a Zoom Server-to-Server OAuth access token.
     *
     * @return string
     */
    private function get_zoom_access_token(): string {
        $accountid = $this->required_config('zoom_accountid');
        $clientid = $this->required_config('zoom_clientid');
        $clientsecret = $this->required_config('zoom_clientsecret');
        $url = self::ZOOM_TOKEN_URL . '?grant_type=account_credentials&account_id=' . rawurlencode($accountid);

        $response = $this->post_form($url, '', [
            'Authorization: Basic ' . base64_encode($clientid . ':' . $clientsecret),
            'Content-Type: application/x-www-form-urlencoded',
        ], 'Zoom');

        if (empty($response->access_token)) {
            throw new moodle_exception('liveintegrationtokenerror', 'local_cmc_lms', '', 'Zoom');
        }

        return (string)$response->access_token;
    }

    /**
     * Create a Google Meet meeting space using OAuth JWT flow.
     *
     * @param stdClass $program CMC program record.
     * @param stdClass $course Moodle course record.
     * @param stdClass $metadata Submitted program-course metadata.
     * @return stdClass
     */
    private function create_google_meet_space(stdClass $program, stdClass $course, stdClass $metadata): stdClass {
        if (!get_config('local_cmc_lms', 'google_meet_enabled')) {
            throw new moodle_exception('liveintegrationdisabled', 'local_cmc_lms', '', 'Google Meet');
        }

        $token = $this->get_google_access_token();
        $response = $this->post_json(self::GOOGLE_MEET_SPACES_URL, (object)[], [
            'Authorization: Bearer ' . $token,
        ]);

        if (empty($response->meetingUri) || empty($response->name)) {
            throw new moodle_exception('liveintegrationmissingurl', 'local_cmc_lms', '', 'Google Meet');
        }

        return (object)[
            'provider' => self::PROVIDER_MEET,
            'externalid' => (string)$response->name,
            'joinurl' => (string)$response->meetingUri,
        ];
    }

    /**
     * Request a Google OAuth access token for Meet API.
     *
     * @return string
     */
    private function get_google_access_token(): string {
        $serviceaccount = $this->required_config('google_meet_service_account');
        $privatekey = $this->normalise_private_key($this->required_config('google_meet_private_key'));
        $subject = trim((string)get_config('local_cmc_lms', 'google_meet_subject'));
        $now = time();

        $claims = [
            'iss' => $serviceaccount,
            'scope' => 'https://www.googleapis.com/auth/meetings.space.created',
            'aud' => self::GOOGLE_TOKEN_URL,
            'exp' => $now + HOURSECS,
            'iat' => $now,
        ];
        if ($subject !== '') {
            $claims['sub'] = $subject;
        }

        $jwt = $this->sign_jwt($claims, $privatekey);
        $response = $this->post_form(self::GOOGLE_TOKEN_URL, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ], '', '&'), [
            'Content-Type: application/x-www-form-urlencoded',
        ], 'Google Meet');

        if (empty($response->access_token)) {
            throw new moodle_exception('liveintegrationtokenerror', 'local_cmc_lms', '', 'Google Meet');
        }

        return (string)$response->access_token;
    }

    /**
     * Sign a JWT using RS256.
     *
     * @param array $claims JWT claims.
     * @param string $privatekey PEM private key.
     * @return string
     */
    private function sign_jwt(array $claims, string $privatekey): string {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $payload = $this->base64url_encode(json_encode($header)) . '.' . $this->base64url_encode(json_encode($claims));
        $signature = '';

        if (!openssl_sign($payload, $signature, $privatekey, OPENSSL_ALGO_SHA256)) {
            throw new moodle_exception('liveintegrationjwterror', 'local_cmc_lms');
        }

        return $payload . '.' . $this->base64url_encode($signature);
    }

    /**
     * POST JSON and decode the JSON response.
     *
     * @param string $url Endpoint URL.
     * @param array|stdClass $payload Request payload.
     * @param string[] $headers Extra HTTP headers.
     * @return stdClass
     */
    private function post_json(string $url, $payload, array $headers = []): stdClass {
        $headers[] = 'Content-Type: application/json';
        return $this->post_raw($url, json_encode($payload), $headers, 'live session provider');
    }

    /**
     * POST form data and decode the JSON response.
     *
     * @param string $url Endpoint URL.
     * @param string $payload Encoded form payload.
     * @param string[] $headers HTTP headers.
     * @param string $provider Provider name for error reporting.
     * @return stdClass
     */
    private function post_form(string $url, string $payload, array $headers, string $provider): stdClass {
        return $this->post_raw($url, $payload, $headers, $provider);
    }

    /**
     * POST raw data and decode the JSON response.
     *
     * @param string $url Endpoint URL.
     * @param string $payload Request payload.
     * @param string[] $headers HTTP headers.
     * @param string $provider Provider name for error reporting.
     * @return stdClass
     */
    private function post_raw(string $url, string $payload, array $headers, string $provider): stdClass {
        $curl = new \curl();
        $response = $curl->post($url, $payload, [
            'CURLOPT_HTTPHEADER' => $headers,
            'CURLOPT_CONNECTTIMEOUT' => 15,
            'CURLOPT_TIMEOUT' => 30,
        ]);
        $info = $curl->get_info();
        $httpcode = (int)($info['http_code'] ?? 0);
        $decoded = json_decode((string)$response);

        if ($httpcode < 200 || $httpcode >= 300 || !is_object($decoded)) {
            $message = $this->response_error_message($decoded, (string)$response);
            throw new moodle_exception('liveintegrationapierror', 'local_cmc_lms', '', $provider . ': ' . $message);
        }

        return $decoded;
    }

    /**
     * Return a configured plugin setting or throw a Moodle exception.
     *
     * @param string $name Config setting name.
     * @return string
     */
    private function required_config(string $name): string {
        $value = trim((string)get_config('local_cmc_lms', $name));
        if ($value === '') {
            throw new moodle_exception('missingintegrationconfig', 'local_cmc_lms', '', $name);
        }

        return $value;
    }

    /**
     * Build a provider-safe session topic.
     *
     * @param stdClass $program CMC program record.
     * @param stdClass $course Moodle course record.
     * @param stdClass $metadata Submitted program-course metadata.
     * @return string
     */
    private function session_topic(stdClass $program, stdClass $course, stdClass $metadata): string {
        if (!empty($metadata->contentlabel)) {
            return clean_param($metadata->contentlabel, PARAM_TEXT);
        }

        return clean_param($program->name . ' - ' . $course->fullname, PARAM_TEXT);
    }

    /**
     * Build a short session description.
     *
     * @param stdClass $program CMC program record.
     * @param stdClass $course Moodle course record.
     * @return string
     */
    private function session_description(stdClass $program, stdClass $course): string {
        return clean_param('CMC LMS: ' . $program->name . ' / ' . $course->fullname, PARAM_TEXT);
    }

    /**
     * Extract a safe error message from provider response.
     *
     * @param mixed $decoded Decoded JSON response.
     * @param string $raw Raw response body.
     * @return string
     */
    private function response_error_message($decoded, string $raw): string {
        if (is_object($decoded)) {
            if (!empty($decoded->message)) {
                return clean_param((string)$decoded->message, PARAM_TEXT);
            }
            if (!empty($decoded->error_description)) {
                return clean_param((string)$decoded->error_description, PARAM_TEXT);
            }
            if (!empty($decoded->error)) {
                return clean_param((string)$decoded->error, PARAM_TEXT);
            }
        }

        return substr(clean_param($raw, PARAM_TEXT), 0, 300);
    }

    /**
     * Normalize private keys pasted in settings with escaped newlines.
     *
     * @param string $privatekey Raw private key value.
     * @return string
     */
    private function normalise_private_key(string $privatekey): string {
        return str_replace('\\n', "\n", trim($privatekey));
    }

    /**
     * Base64url encode binary data.
     *
     * @param string $data Data to encode.
     * @return string
     */
    private function base64url_encode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
