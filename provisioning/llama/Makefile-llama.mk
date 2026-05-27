# Host-native llama.cpp lifecycle. macOS-only. Intended to run from the
# repo root on the deploy host (io.marianne.aerilon).
#
# Required env: LLAMA_CHAT_MODEL (set in .env.local; resolves the chat
# GGUF filename via provisioning/llama/models.lock).

.PHONY: llama-install llama-uninstall llama-start llama-stop llama-restart \
        llama-status llama-logs llama-doctor llama-models-download chat-smoke

LLAMA_DIR     := provisioning/llama
LLAMA_AGENTS  := io.shal.llama-chat io.shal.llama-embed
LLAMA_BIN     := /opt/homebrew/bin/llama-server
LLAMA_VERSION := $(shell cat $(LLAMA_DIR)/llama-cpp.version 2>/dev/null)

llama-install: ## Install llama.cpp (brew) + render plists + download models + run doctor
	@echo "==> llama-install: ensuring llama.cpp is installed"
	@brew list llama.cpp >/dev/null 2>&1 || brew install llama.cpp
	@brew pin llama.cpp >/dev/null 2>&1 || true
	@$(LLAMA_BIN) --version 2>&1 | head -1 > $(LLAMA_DIR)/INSTALLED.version
	@echo "==> llama-install: installed version recorded in $(LLAMA_DIR)/INSTALLED.version"
	@$(MAKE) llama-models-download
	@bash $(LLAMA_DIR)/scripts/install-launchagents.sh
	@$(MAKE) llama-doctor

llama-uninstall: ## Unload + remove LaunchAgents (leaves models + logs)
	@echo "==> llama-uninstall: removing LaunchAgents"
	@bash $(LLAMA_DIR)/scripts/uninstall-launchagents.sh

llama-models-download: ## Download GGUF models per models.lock (idempotent, sha256-verified)
	@echo "==> llama-models-download: syncing var/llama/models/ against models.lock"
	@bash $(LLAMA_DIR)/scripts/download-models.sh

llama-start: ## Start both llama-server processes via launchd
	@echo "==> llama-start: kickstarting $(LLAMA_AGENTS)"
	@for a in $(LLAMA_AGENTS); do launchctl kickstart -k "gui/$$(id -u)/$$a"; done

llama-stop: ## Stop both llama-server processes (clean exit, no auto-restart)
	@echo "==> llama-stop: stopping $(LLAMA_AGENTS)"
	@for a in $(LLAMA_AGENTS); do launchctl stop "gui/$$(id -u)/$$a" 2>/dev/null || true; done

llama-restart: llama-stop llama-start ## Stop + start

llama-status: ## Print launchd status for both agents
	@for a in $(LLAMA_AGENTS); do echo "==> $$a"; launchctl print "gui/$$(id -u)/$$a" 2>/dev/null | head -20 || echo "  not loaded"; done

llama-logs: ## Tail both llama-server log files
	@tail -F var/llama/logs/chat.out.log var/llama/logs/chat.err.log var/llama/logs/embed.out.log var/llama/logs/embed.err.log

llama-doctor: ## Verify installed version matches pin + both ports respond
	@echo "==> llama-doctor: checking installed version against pin"
	@installed="$$($(LLAMA_BIN) --version 2>&1 | head -1)"; \
	 expected="$(LLAMA_VERSION)"; \
	 if [ -z "$$expected" ]; then echo "  WARN: $(LLAMA_DIR)/llama-cpp.version is empty"; \
	 elif echo "$$installed" | grep -q "$$expected"; then echo "  ok: $$installed matches pin $$expected"; \
	 else echo "  FAIL: installed=$$installed pin=$$expected (bump llama-cpp.version after intentional upgrade)"; exit 1; fi
	@echo "==> llama-doctor: GET /v1/models on :8081 (chat)"
	@curl -sf http://127.0.0.1:8081/v1/models >/dev/null && echo "  ok" || (echo "  FAIL"; exit 1)
	@echo "==> llama-doctor: GET /v1/models on :8082 (embed)"
	@curl -sf http://127.0.0.1:8082/v1/models >/dev/null && echo "  ok" || (echo "  FAIL"; exit 1)
	@echo "==> llama-doctor: all checks passed"

chat-smoke: ## Full end-to-end smoke test (assumes llama servers + php-worker container are up)
	@bash $(LLAMA_DIR)/scripts/chat-smoke.sh
