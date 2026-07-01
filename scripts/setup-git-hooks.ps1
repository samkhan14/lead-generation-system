# Enable repo git hooks (pre-commit chat export).
# Run once per clone from the project root.

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

git config core.hooksPath .githooks
Write-Host "Git hooks enabled: core.hooksPath = .githooks"
Write-Host "Pre-commit will export docs/chats/ from docs/cursor-chat-export.json"
