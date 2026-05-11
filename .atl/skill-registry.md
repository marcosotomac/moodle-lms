# Skill Registry

**Delegator use only.** Any agent that launches sub-agents reads this registry to resolve compact rules, then injects them directly into sub-agent prompts. Sub-agents do NOT read this registry or individual SKILL.md files.

See `_shared/skill-resolver.md` for the full resolution protocol.

## User Skills

| Trigger | Skill | Path |
|---------|-------|------|
| When creating a GitHub issue, reporting a bug, or requesting a feature. | issue-creation | /Users/marcosotomaceda/.config/opencode/skills/issue-creation/SKILL.md |
| When creating a pull request, opening a PR, or preparing changes for review. | branch-pr | /Users/marcosotomaceda/.config/opencode/skills/branch-pr/SKILL.md |
| When user asks to create a new skill, add agent instructions, or document patterns for AI. | skill-creator | /Users/marcosotomaceda/.config/opencode/skills/skill-creator/SKILL.md |
| When writing Go tests, using teatest, or adding test coverage. | go-testing | /Users/marcosotomaceda/.config/opencode/skills/go-testing/SKILL.md |
| When user says "judgment day", "judgment-day", "review adversarial", "dual review", "doble review", "juzgar", "que lo juzguen". | judgment-day | /Users/marcosotomaceda/.config/opencode/skills/judgment-day/SKILL.md |
| — | confluence-curl-fallback | /Users/marcosotomaceda/.agents/skills/confluence-curl-fallback/SKILL.md |
| — | codex-subagent-catalog | /Users/marcosotomaceda/.agents/skills/codex-subagent-catalog/SKILL.md |
| — | frontend-design | /Users/marcosotomaceda/.agents/skills/frontend-design/SKILL.md |
| — | odoo-development | /Users/marcosotomaceda/.agents/skills/odoo-development/SKILL.md |

## Compact Rules

Pre-digested rules per skill. Delegators copy matching blocks into sub-agent prompts as `## Project Standards (auto-resolved)`.

### issue-creation
- Always use the official issue templates (bug or feature); blank issues are disabled.
- Search existing issues for duplicates before creating a new one.
- Every new issue gets `status:needs-review` automatically.
- A maintainer must add `status:approved` before any PR can be opened.
- Questions go to Discussions, not issues.
- Fill all required template fields and pre-flight checkboxes.

### branch-pr
- Every PR MUST link an approved issue (`status:approved`).
- PRs MUST include exactly one `type:*` label that matches the template checkbox.
- Branch names must match: `^(feat|fix|chore|docs|style|refactor|perf|test|build|ci|revert)/[a-z0-9._-]+$`.
- Use conventional commits: `type(scope): description` or `type: description`.
- Use the PR template and include Closes/Fixes/Resolves #N.
- Run shellcheck on modified scripts before PR.

### skill-creator
- Create a skill only for reusable, non-trivial patterns.
- Follow the required skill structure and SKILL.md frontmatter fields.
- Keep Critical Patterns concise and actionable; avoid duplication of docs.
- References must point to LOCAL files, not web URLs.
- Add the new skill to AGENTS.md after creation.

### go-testing
- Prefer table-driven tests for multiple cases.
- Test Bubbletea models by asserting Update() state transitions.
- Use teatest for full interactive TUI flows.
- Use golden files for UI output comparisons when appropriate.
- Use t.TempDir() for filesystem isolation in tests.

### judgment-day
- Resolve skill registry and inject compact rules before launching judges.
- Launch two blind judges in parallel via delegate.
- Classify warnings as real vs theoretical based on normal-user trigger.
- Fix only confirmed issues; re-judge after fixes.
- After 2 fix iterations, ask the user before continuing.
- Never declare APPROVED until confirmed criteria are met.

### confluence-curl-fallback
- Use curl + Confluence API v2 only when MCP fails (401/403/404).
- For updates, increment the version number (current + 1).
- Handle 401/403/409 errors explicitly and retry with correct version.
- Use JSON body with storage representation for page content.

### codex-subagent-catalog
- Default to single-agent; add validation for non-trivial changes.
- Use multi-agent only for cross-subsystem or high-risk work.
- Never let two workers share a write scope.
- Use the required handoff template for delegations.

### frontend-design
- Commit to a bold, intentional aesthetic direction before coding.
- Avoid generic fonts/palettes and clichéd layouts.
- Use distinctive typography, strong palette, and deliberate motion.
- Match implementation complexity to the chosen aesthetic.

### odoo-development
- Use Odoo ORM with proper API decorators and PEP 8.
- Implement views via XML inheritance (no core edits).
- Enforce validation with constraints and Odoo exceptions.
- Define ACLs and security groups for access control.
- Keep modules modular with clear structure and manifest.

## Project Conventions

| File | Path | Notes |
|------|------|-------|
| — | — | No project convention files found |

Read the convention files listed above for project-specific patterns and rules. All referenced paths have been extracted — no need to read index files to discover more.
