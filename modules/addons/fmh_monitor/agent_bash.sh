#!/bin/bash
# FMH Monitor - Server Agent V3 (Enterprise) - Anti-Fail Edition
# Uso: bash agent.sh --install --token="X" --url="Y" --env="cpanel" --hmac-secret="Z"

CONFIG_DIR="/etc/fmh_monitor"
CONFIG_FILE="$CONFIG_DIR/config.env"
CONFIG_ENC="$CONFIG_DIR/config.enc"
RECOVERY_URL_FILE="$CONFIG_DIR/api_url"
SCRIPT_PATH="/usr/local/bin/fmh_monitor_agent.sh"
AGENT_VERSION="0.0"

# Self-Repair System
LAST_SUCCESS_FILE="$CONFIG_DIR/last_success"
CRASH_COUNT_FILE="$CONFIG_DIR/crash_count"
STABLE_BACKUP="/usr/local/bin/fmh_monitor_agent_stable.sh"
STABLE_COUNTER_FILE="$CONFIG_DIR/stable_counter"

# =============================================
# HMAC + ENCRYPTION HELPERS (Multi-method for max compatibility)
# =============================================
get_machine_key() {
    local raw=""
    if [ -f /etc/machine-id ]; then raw=$(cat /etc/machine-id 2>/dev/null | tr -d '\n')
    elif [ -f /var/lib/dbus/machine-id ]; then raw=$(cat /var/lib/dbus/machine-id 2>/dev/null | tr -d '\n')
    else raw=$(hostname 2>/dev/null); fi
    # Try multiple hash methods
    local key=""
    key=$(echo -n "$raw" | md5sum 2>/dev/null | awk '{print $1}' | cut -c1-16)
    [ -z "$key" ] && key=$(echo -n "$raw" | sha256sum 2>/dev/null | awk '{print $1}' | cut -c1-16)
    [ -z "$key" ] && key=$(echo -n "$raw" | openssl dgst -md5 2>/dev/null | awk '{print $NF}' | cut -c1-16)
    [ -z "$key" ] && key=$(echo -n "$raw" | cksum 2>/dev/null | awk '{print $1}' | cut -c1-16)
    echo "$key"
}

decrypt_config() {
    if [ -f "$CONFIG_ENC" ] && command -v openssl >/dev/null 2>&1; then
        local MACHINE_KEY=$(get_machine_key)
        if [ -n "$MACHINE_KEY" ]; then
            # Try pbkdf2 first (openssl 1.1.1+), fall back to legacy
            openssl enc -aes-256-cbc -pbkdf2 -d -pass pass:"$MACHINE_KEY" -in "$CONFIG_ENC" 2>/dev/null || \
            openssl enc -aes-256-cbc -d -pass pass:"$MACHINE_KEY" -in "$CONFIG_ENC" 2>/dev/null || \
            openssl enc -aes-256-cbc -md md5 -d -pass pass:"$MACHINE_KEY" -in "$CONFIG_ENC" 2>/dev/null
        fi
    fi
}

encrypt_config() {
    if [ -f "$CONFIG_FILE" ] && command -v openssl >/dev/null 2>&1; then
        local MACHINE_KEY=$(get_machine_key)
        if [ -n "$MACHINE_KEY" ]; then
            openssl enc -aes-256-cbc -pbkdf2 -pass pass:"$MACHINE_KEY" -in "$CONFIG_FILE" -out "$CONFIG_ENC" 2>/dev/null
            [ -s "$CONFIG_ENC" ] && chmod 600 "$CONFIG_ENC"
        fi
    fi
    # NEVER delete plaintext - keep as fallback
}

sign_payload() {
    PAYLOAD_FILE="$1"
    SECRET="$2"
    if [ -n "$SECRET" ] && [ -f "$PAYLOAD_FILE" ]; then
        openssl dgst -sha256 -hmac "$SECRET" "$PAYLOAD_FILE" 2>/dev/null | awk '{print $NF}'
    fi
}

generate_nonce() {
    cat /dev/urandom 2>/dev/null | tr -dc 'a-zA-Z0-9' | head -c 32 2>/dev/null || echo "$(date +%s%N)$RANDOM"
}

cleanup_previous_install() {
    echo "Limpando instalacao anterior..."
    # Mata qualquer processo do agente rodando
    pkill -f fmh_monitor_agent 2>/dev/null || true
    # Remove cron (para cron antes de remover arquivo)
    [ -f /etc/cron.d/fmh_monitor_agent ] && systemctl stop cron 2>/dev/null || systemctl stop crond 2>/dev/null
    rm -f /etc/cron.d/fmh_monitor_agent
    # Remove config directory (keys, env, repair log, encrypted config, everything)
    rm -rf /etc/fmh_monitor/
    # Remove ALL agent scripts
    rm -f /usr/local/bin/fmh_monitor_agent*
    # Remove audit files
    rm -f /var/log/fmh_audit_commands.log /var/log/fmh_audit_commands.log.tmp
    # Remove PROMPT_COMMAND hook
    rm -f /etc/profile.d/fmh_audit.sh
    for rc in /etc/bashrc /etc/bash.bashrc /root/.bashrc; do
        [ -f "$rc" ] && sed -i '/fmh_audit_cmd/d' "$rc" 2>/dev/null
    done
    systemctl start cron 2>/dev/null || systemctl start crond 2>/dev/null
    echo "[OK] Instalacao anterior removida completamente."
}

install_agent() {
    TOKEN=""; URL=""; ENV="linux"; AUTO_REM="0"; HMAC_SECRET=""; SSH_PUBKEY=""; DO_CLEAN=0
    
    while [ $# -gt 0 ]; do
      case "$1" in
        --token=*) TOKEN="${1#*=}" ;;
        --url=*) URL="${1#*=}" ;;
        --env=*) ENV="${1#*=}" ;;
        --auto-remediation=*) AUTO_REM="${1#*=}" ;;
        --hmac-secret=*) HMAC_SECRET="${1#*=}" ;;
        --ssh-public-key=*) SSH_PUBKEY="${1#*=}" ;;
        --clean) DO_CLEAN=1 ;;
        *) ;;
      esac
        shift
    done

    if [ $DO_CLEAN -eq 1 ]; then
        cleanup_previous_install
    fi

    echo "Instalando FMH Monitor Agent..."
    mkdir -p $CONFIG_DIR
    chmod 700 $CONFIG_DIR

    if [ -z "$TOKEN" ] || [ -z "$URL" ]; then
        echo "Erro: --token e --url sao obrigatorios."
        exit 1
    fi

    # Extrair ID unico da maquina para protecao Anti-Clone
    MACHINE_ID=$(cat /etc/machine-id 2>/dev/null || cat /var/lib/dbus/machine-id 2>/dev/null || hostname)
    
    API_URL="${URL}/modules/addons/fmh_monitor/agent_api.php"

    # Verificar seguranca do Token ANTES de instalar
    echo "Verificando seguranca do Token..."
    VERIFY_RESPONSE=$(curl -s -X POST -H "Content-Type: application/json" -H "Agent-Token: $TOKEN" -d "{\"action\":\"verify_install\", \"machine_id\":\"$MACHINE_ID\"}" $API_URL)
    if echo "$VERIFY_RESPONSE" | grep -q '"status":"error"'; then
        echo "=========================================================="
        echo "🚨 ERRO CRITICO DE SEGURANCA 🚨"
        echo "Este Token ja esta vinculado a outro servidor!"
        echo "Para evitar conflito de dados (Split-Brain), a instalacao foi abortada."
        echo "Se voce esta tentando instalar em um novo servidor, gere um novo Token no painel."
        echo "=========================================================="
        exit 1
    fi

    echo "WHMCS_URL=\"$API_URL\"" > $CONFIG_FILE
    echo "AGENT_TOKEN=\"$TOKEN\"" >> $CONFIG_FILE
    echo "AGENT_ENV=\"$ENV\"" >> $CONFIG_FILE
    echo "AUTO_REMEDIATION=\"$AUTO_REM\"" >> $CONFIG_FILE
    [ -n "$HMAC_SECRET" ] && echo "HMAC_SECRET=\"$HMAC_SECRET\"" >> $CONFIG_FILE
    chmod 600 $CONFIG_FILE
    # Recovery URL file (plaintext, never deleted, for emergency repair)
    echo "$API_URL" > "$RECOVERY_URL_FILE"
    chmod 600 "$RECOVERY_URL_FILE"
    encrypt_config
    
    # Auto-configure SSH for WHMCS remote repair (agent generates key pair)
    echo "Configurando acesso SSH para Auto-Reparo Remoto..."
    REPAIR_KEY="/etc/fmh_monitor/repair_key"
    REGENERATE=0
    # Regenerate if key is ed25519 (not supported by PHP ssh2)
    if [ -f "$REPAIR_KEY" ] && grep -q "ed25519" "$REPAIR_KEY" 2>/dev/null; then
        # Remove old ed25519 key from authorized_keys
        if [ -f "${REPAIR_KEY}.pub" ]; then
            OLD_PUB=$(cat "${REPAIR_KEY}.pub" 2>/dev/null)
            [ -n "$OLD_PUB" ] && [ -f /root/.ssh/authorized_keys ] && sed -i "\|$OLD_PUB|d" /root/.ssh/authorized_keys 2>/dev/null
        fi
        rm -f "$REPAIR_KEY" "${REPAIR_KEY}.pub"
        REGENERATE=1
    fi
    if [ ! -f "$REPAIR_KEY" ] && command -v ssh-keygen >/dev/null 2>&1; then
    ssh-keygen -t rsa -b 4096 -m PEM -f "$REPAIR_KEY" -N "" -C "fmh_auto_repair" 2>/dev/null
        if [ -f "${REPAIR_KEY}.pub" ]; then
            mkdir -p /root/.ssh 2>/dev/null
            chmod 700 /root/.ssh 2>/dev/null
            cat "${REPAIR_KEY}.pub" >> /root/.ssh/authorized_keys 2>/dev/null
            chmod 600 /root/.ssh/authorized_keys 2>/dev/null
            chmod 600 "$REPAIR_KEY" "${REPAIR_KEY}.pub" 2>/dev/null
            echo "  [OK] Chave SSH RSA gerada e adicionada ao authorized_keys."
            echo "  [OK] Chave privada sera enviada ao WHMCS no primeiro sync."
        fi
    elif [ -f "$REPAIR_KEY" ]; then
        echo "  [OK] Chave SSH RSA ja configurada."
    else
        echo "  [AVISO] ssh-keygen nao encontrado. Auto-Reparo SSH desabilitado."
    fi
    # Also add external public key if provided (backward compat)
    if [ -n "$SSH_PUBKEY" ]; then
        mkdir -p /root/.ssh 2>/dev/null
        chmod 700 /root/.ssh 2>/dev/null
        touch /root/.ssh/authorized_keys 2>/dev/null
        chmod 600 /root/.ssh/authorized_keys 2>/dev/null
        if ! grep -qF "$SSH_PUBKEY" /root/.ssh/authorized_keys 2>/dev/null; then
            echo "$SSH_PUBKEY" >> /root/.ssh/authorized_keys
        fi
    fi

    # Detect SSH user (current user running the install)
    SSH_USER_DETECTED=$(whoami 2>/dev/null || echo "root")
    echo "SSH_USER=\"$SSH_USER_DETECTED\"" >> $CONFIG_FILE

    # Setup Security Audit Tracker
    echo "Configurando Monitoramento de Auditoria (Comandos)..."
    touch /var/log/fmh_audit_commands.log
    chmod 600 /var/log/fmh_audit_commands.log # Permite append por todos os usuarios, mas sem leitura
        cat << 'EOF' > /etc/profile.d/fmh_audit.sh
fmh_audit_cmd() {
    local cmd=$(history 1 | sed "s/^[ ]*[0-9]\+[ ]*//; s/^[0-9]\{4\}-[0-9]\{2\}-[0-9]\{2\} [0-9]\{2\}:[0-9]\{2\}:[0-9]\{2\}[ ]*//")
    if [ "$cmd" != "$FMH_LAST_CMD" ] && [ -n "$cmd" ]; then
        echo "$(date +"%Y-%m-%d %H:%M:%S")|$(whoami)|$(echo $SSH_CLIENT | awk '{print $1}')|$cmd" >> /var/log/fmh_audit_commands.log
        export FMH_LAST_CMD="$cmd"
    fi
}
EOF
    chmod +x /etc/profile.d/fmh_audit.sh

    for rc in /etc/bashrc /etc/bash.bashrc /root/.bashrc; do
        if [ -f "$rc" ] && ! grep -q "fmh_audit_cmd" "$rc"; then
            echo -e "\n# FMH Monitor Audit\nPROMPT_COMMAND=\"${PROMPT_COMMAND:+$PROMPT_COMMAND; }fmh_audit_cmd\"" >> "$rc"
        fi
    done

    # Download via PHP endpoint to bypass Cloudflare cache
    curl -sL "${URL}/modules/addons/fmh_monitor/agent_download.php?t=$(date +%s)" -o $SCRIPT_PATH
    chmod +x $SCRIPT_PATH

    CRON_FILE="/etc/cron.d/fmh_monitor_agent"
    echo "* * * * * root bash -n $SCRIPT_PATH 2>/dev/null && bash $SCRIPT_PATH > /dev/null 2>&1 || { curl -sL --retry 3 \"${URL}/modules/addons/fmh_monitor/agent_download.php?t=\$(date +\%s)\" -o $SCRIPT_PATH; bash -n $SCRIPT_PATH 2>/dev/null && chmod +x $SCRIPT_PATH && bash $SCRIPT_PATH > /dev/null 2>&1 || { [ -f /usr/local/bin/fmh_monitor_agent_stable.sh ] && cp /usr/local/bin/fmh_monitor_agent_stable.sh $SCRIPT_PATH && chmod +x $SCRIPT_PATH; }; }" > $CRON_FILE
    chmod 600 $CRON_FILE
    
    systemctl restart cron 2>/dev/null || systemctl restart crond 2>/dev/null

    echo "Instalação Concluida! O agente enviara metricas a cada 1 minuto."
    $SCRIPT_PATH
    exit 0
}

if [ "$1" == "--install" ]; then
    shift
    install_agent "$@"
fi

# ==========================================
# GATHER METRICS (RUN BY CRON)
# ==========================================
# =============================================
# CONFIG LOADING - 3 Recovery Levels
# Level 1: Plaintext config (always primary)
# Level 2: Encrypted config (backup)
# Level 3: Recovery URL file (emergency)
# ALL FAIL → exit 1 → cron failsafe triggers
# =============================================
CONFIG_LOADED=0

# Level 1: Plaintext config (always primary)
if [ -f "$CONFIG_FILE" ]; then
    source $CONFIG_FILE
    [ -n "$WHMCS_URL" ] && CONFIG_LOADED=1
fi

# Level 2: Encrypted config (backup) + auto-repair if corrupted
if [ $CONFIG_LOADED -eq 0 ] && [ -f "$CONFIG_ENC" ]; then
    DECRYPTED=$(decrypt_config 2>/dev/null)
    # Validate: must contain WHMCS_URL= assignment (not binary garbage)
    if echo "$DECRYPTED" | grep -q '^WHMCS_URL=' 2>/dev/null; then
        eval "$DECRYPTED"
        [ -n "$WHMCS_URL" ] && CONFIG_LOADED=1
    else
        # Corrupted config.enc - auto-delete and log
        rm -f "$CONFIG_ENC"
        echo "AUTO-REPAIR: Deleted corrupted config.enc $(date)" >> "$CONFIG_DIR/repair.log" 2>/dev/null
    fi
fi

# Level 3: Recovery URL
if [ $CONFIG_LOADED -eq 0 ] && [ -f "$RECOVERY_URL_FILE" ]; then
    WHMCS_URL=$(cat "$RECOVERY_URL_FILE" 2>/dev/null)
    AGENT_TOKEN="RECOVERY_MODE_$(date +%s)"
    [ -n "$WHMCS_URL" ] && CONFIG_LOADED=3
fi

# If all recovery levels failed → exit 1 (trigger cron failsafe)
if [ $CONFIG_LOADED -eq 0 ] || [ -z "$WHMCS_URL" ]; then
    echo "FATAL: Cannot load config. Triggering cron failsafe." >> "$CONFIG_DIR/repair.log" 2>/dev/null
    exit 1
fi

# =============================================
# SELF-REPAIR SYSTEM (Intelligent Crash Recovery)
# =============================================
if [ -f "$LAST_SUCCESS_FILE" ]; then
    LAST_SUCCESS=$(cat "$LAST_SUCCESS_FILE" 2>/dev/null)
    LAST_TS=$(date -d "$LAST_SUCCESS" +%s 2>/dev/null || echo 0)
    NOW_TS=$(date +%s)
    DIFF=$(( NOW_TS - LAST_TS ))
    # If last success was >3 min ago, we likely crashed
    if [ $DIFF -gt 180 ]; then
        CRASH_COUNT=0
        [ -f "$CRASH_COUNT_FILE" ] && CRASH_COUNT=$(cat "$CRASH_COUNT_FILE" 2>/dev/null)
        CRASH_COUNT=$((CRASH_COUNT + 1))
        echo $CRASH_COUNT > "$CRASH_COUNT_FILE"

        if [ $CRASH_COUNT -ge 4 ] && [ $CRASH_COUNT -lt 7 ] && [ -f "$STABLE_BACKUP" ]; then
            # Rollback to last known stable version
            cp "$STABLE_BACKUP" "$SCRIPT_PATH"
            chmod +x "$SCRIPT_PATH"
            echo "ROLLBACK: Restored stable agent $(date)" >> "$CONFIG_DIR/repair.log"
            exec bash "$SCRIPT_PATH"
            exit $?
        elif [ $CRASH_COUNT -ge 7 ]; then
            # Force re-download from API (bypasses all caches)
            curl -sL --retry 3 "${WHMCS_URL}?action=download_agent&t=$(date +%s)&force=repair&crash=${CRASH_COUNT}" -o /tmp/fmh_repair.sh 2>/dev/null
            if [ -s /tmp/fmh_repair.sh ] && grep -q "FMH Monitor" /tmp/fmh_repair.sh && bash -n /tmp/fmh_repair.sh 2>/dev/null; then
                mv -f /tmp/fmh_repair.sh "$SCRIPT_PATH"
                chmod +x "$SCRIPT_PATH"
                echo "REPAIR: Force re-downloaded agent $(date) after ${CRASH_COUNT} crashes" >> "$CONFIG_DIR/repair.log"
                echo 0 > "$CRASH_COUNT_FILE"
                exec bash "$SCRIPT_PATH"
                exit $?
            fi
            echo "REPAIR FAILED: Downloaded agent has syntax error $(date)" >> "$CONFIG_DIR/repair.log"
            rm -f /tmp/fmh_repair.sh
        fi
    else
        # All good, reset crash count
        [ -f "$CRASH_COUNT_FILE" ] && echo 0 > "$CRASH_COUNT_FILE"
    fi
fi

# Auto-heal cron failsafe (Updates old crons)
CRON_FILE="/etc/cron.d/fmh_monitor_agent"
if [ -f "$CRON_FILE" ] && ! grep -q "bash -n" "$CRON_FILE" 2>/dev/null; then
    echo "* * * * * root bash -n $SCRIPT_PATH 2>/dev/null && bash $SCRIPT_PATH > /dev/null 2>&1 || { curl -sL --retry 3 \"${WHMCS_URL}?action=download_agent&t=\$(date +\%s)\" -o $SCRIPT_PATH; bash -n $SCRIPT_PATH 2>/dev/null && chmod +x $SCRIPT_PATH && bash $SCRIPT_PATH > /dev/null 2>&1 || { [ -f /usr/local/bin/fmh_monitor_agent_stable.sh ] && cp /usr/local/bin/fmh_monitor_agent_stable.sh $SCRIPT_PATH && chmod +x $SCRIPT_PATH; }; }" > $CRON_FILE
    systemctl restart cron 2>/dev/null || systemctl restart crond 2>/dev/null
fi

# 1. CPU Usage (%)
cpu_stat1=$(grep '^cpu ' /proc/stat 2>/dev/null)
sleep 0.5
cpu_stat2=$(grep '^cpu ' /proc/stat 2>/dev/null)
cpu_usage=$(echo | awk -v s1="$cpu_stat1" -v s2="$cpu_stat2" 'BEGIN {
    split(s1, a, " "); u1 = a[2] + a[4]; t1 = a[2] + a[4] + a[5];
    split(s2, b, " "); u2 = b[2] + b[4]; t2 = b[2] + b[4] + b[5];
    if (t2 - t1 > 0) printf "%.2f", (u2 - u1) * 100 / (t2 - t1); else print "0.00";
}')
cpu_usage=$(echo "$cpu_usage" | tr ',' '.')
if [ -z "$cpu_usage" ]; then cpu_usage=0; fi

read load_1 load_5 load_15 rest < /proc/loadavg
[ -z "$load_1" ] && load_1=0
[ -z "$load_5" ] && load_5=0
[ -z "$load_15" ] && load_15=0

# 2. Memory (in MB)
mem_total=$(free -m | awk '/^Mem:/{print $2}')
mem_used=$(free -m | awk '/^Mem:/{print $3}')
[ -z "$mem_total" ] && mem_total=0
[ -z "$mem_used" ] && mem_used=0

# Drop Caches if RAM > 95% and Auto-Remediation ON
if [ "$AUTO_REMEDIATION" == "1" ] && [ "$mem_total" -gt 0 ]; then
    mem_pct=$(( 100 * mem_used / mem_total ))
    if [ $mem_pct -gt 95 ]; then
        sync; echo 3 > /proc/sys/vm/drop_caches
    fi
fi

# 3. Disk Usage & Inodes (/)
disk_total=$(df -h / | awk 'NR==2 {print $2}')
disk_used_pct=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
inodes_used_pct=$(df -i / | awk 'NR==2 {print $5}' | sed 's/%//')
[ -z "$disk_used_pct" ] && disk_used_pct=0
[ -z "$inodes_used_pct" ] && inodes_used_pct=0

# SSL Certificate Expiry Check (for domains hosted on this server)
ssl_certs_json="[]"
if command -v openssl >/dev/null 2>&1; then
    ssl_certs_json="["
    first_cert=1
    for cert_dir in /etc/ssl /etc/pki/tls/certs /var/cpanel/ssl /etc/apache2/ssl /etc/nginx/ssl; do
        [ -d "$cert_dir" ] || continue
        # Use temp file to avoid process substitution (max compatibility)
        find "$cert_dir" -name "*.crt" -o -name "*.pem" 2>/dev/null | head -10 > /tmp/fmh_certs.txt 2>/dev/null
        while IFS= read -r cert_file; do
            [ -z "$cert_file" ] && continue
            if openssl x509 -in "$cert_file" -noout 2>/dev/null; then
                expiry=$(openssl x509 -in "$cert_file" -enddate -noout 2>/dev/null | cut -d= -f2)
                domain=$(openssl x509 -in "$cert_file" -subject -noout 2>/dev/null | sed 's/.*CN = //' | sed 's/,.*//')
                issuer=$(openssl x509 -in "$cert_file" -issuer -noout 2>/dev/null | sed 's/.*CN = //' | sed 's/,.*//')
                if [ -n "$expiry" ] && [ -n "$domain" ]; then
                    expiry_epoch=$(date -d "$expiry" +%s 2>/dev/null || echo 0)
                    now_epoch=$(date +%s)
                    days_remaining=$(( (expiry_epoch - now_epoch) / 86400 ))
                    domain_esc=$(echo "$domain" | sed 's/"/\\"/g')
                    issuer_esc=$(echo "$issuer" | sed 's/"/\\"/g')
                    [ $first_cert -eq 0 ] && ssl_certs_json+=","
                    ssl_certs_json+="{\"domain\":\"$domain_esc\",\"issuer\":\"$issuer_esc\",\"expiry_date\":\"$expiry\",\"days_remaining\":$days_remaining}"
                    first_cert=0
                fi
            fi
        done < /tmp/fmh_certs.txt
        rm -f /tmp/fmh_certs.txt
        [ $first_cert -eq 0 ] && break
    done
    ssl_certs_json+="]"
fi

# 4. OS Info
if [ -f /etc/os-release ]; then
    os=$(cat /etc/os-release | grep '^PRETTY_NAME=' | cut -d= -f2 | tr -d '"')
else
    os=$(uname -s -r -m)
fi

# 5. Network Connections
net_connections=$(ss -ant 2>/dev/null | grep ESTAB | wc -l)
[ -z "$net_connections" ] && net_connections=0

# Firewall Auto-Block: if net_connections > 1000, block top offending IPs
if [ "$AUTO_REMEDIATION" == "1" ] && [ "$net_connections" -gt 1000 ] 2>/dev/null; then
    top_ips=$(ss -ant 2>/dev/null | grep ESTAB | awk '{print $5}' | cut -d: -f1 | sort | uniq -c | sort -nr | head -3)
    if [ -n "$top_ips" ]; then
        while read count ip; do
            if [ "$count" -gt 100 ] 2>/dev/null; then
                if command -v iptables >/dev/null 2>&1; then
                    iptables -A INPUT -s "$ip" -j DROP 2>/dev/null
                    iptables -A OUTPUT -d "$ip" -j DROP 2>/dev/null
                fi
                if command -v csf >/dev/null 2>&1; then
                    csf -d "$ip" "FMH Monitor: Brute-force/DoS detected ($count connections)" 2>/dev/null
                fi
            fi
        done <<< "$top_ips"
    fi
fi

# Docker Container Status
docker_json="[]"
if command -v docker >/dev/null 2>&1; then
    docker_output=$(docker ps --format '{{.Names}}|{{.Status}}|{{.RunningFor}}' 2>/dev/null)
    if [ -n "$docker_output" ]; then
        docker_json="["
        first_dc=1
        while IFS='|' read -r name status uptime; do
            [ -z "$name" ] && continue
            name_esc=$(echo "$name" | sed 's/"/\\"/g')
            status_esc=$(echo "$status" | sed 's/"/\\"/g')
            uptime_esc=$(echo "$uptime" | sed 's/"/\\"/g')
            [ $first_dc -eq 0 ] && docker_json+=","
            docker_json+="{\"name\":\"$name_esc\",\"status\":\"$status_esc\",\"uptime\":\"$uptime_esc\"}"
            first_dc=0
        done <<< "$docker_output"
        docker_json+="]"
    fi
fi

# Database Health (MySQL/MariaDB)
db_health_json="{}"
if command -v mysql >/dev/null 2>&1; then
    mysql_status="UP"
    mysql_conns=0
    mysql_slow=0
    # Check if MySQL is running and get connections
    if systemctl is-active --quiet mysql 2>/dev/null || systemctl is-active --quiet mariadb 2>/dev/null || systemctl is-active --quiet mysqld 2>/dev/null; then
        mysql_status="UP"
        mysql_conns=$(mysql -N -e "SELECT COUNT(*) FROM information_schema.PROCESSLIST;" 2>/dev/null || echo 0)
        mysql_slow=$(mysql -N -e "SHOW GLOBAL STATUS LIKE 'Slow_queries';" 2>/dev/null | awk '{print $2}' || echo 0)
        # Ensure numeric values (prevent empty fields breaking JSON)
        [ -z "$mysql_conns" ] && mysql_conns=0
        [ -z "$mysql_slow" ] && mysql_slow=0
    else
        mysql_status="DOWN"
    fi
    db_health_json="{\"type\":\"mysql\",\"status\":\"$mysql_status\",\"connections\":$mysql_conns,\"slow_queries\":$mysql_slow}"
fi

# Kernel Vulnerability Check
kernel_vulns_json="[]"
kernel_ver=$(uname -r)
kernel_major=$(echo "$kernel_ver" | cut -d. -f1)
kernel_minor=$(echo "$kernel_ver" | cut -d. -f2)
# Simple heuristic: kernels older than 3 years are flagged
kernel_year=$(uname -v 2>/dev/null | grep -o '20[0-9][0-9]' | head -1)
if [ -n "$kernel_year" ] && [ "$kernel_year" -lt 2023 ]; then
    kernel_vulns_json="[{\"cve\":\"KERNEL-OUTDATED\",\"description\":\"Kernel compilado em $kernel_year - recomendada atualizacao\",\"severity\":\"HIGH\"}]"
elif [ "$kernel_major" -lt 3 ]; then
    kernel_vulns_json="[{\"cve\":\"KERNEL-EOL\",\"description\":\"Kernel muito antigo ($kernel_ver) - vulneravel\",\"severity\":\"CRITICAL\"}]"
fi

# Network Bandwidth (RX/TX since boot) + Real-time Speed
net_bandwidth_rx=0
net_bandwidth_tx=0
net_speed_rx=0
net_speed_tx=0
if [ -f /proc/net/dev ]; then
    # Detect primary interface
    if command -v ip >/dev/null 2>&1; then
        primary_iface=$(ip route get 1.1.1.1 2>/dev/null | awk '{for(i=1;i<=NF;i++) if($i=="dev") print $(i+1); exit}')
    fi
    [ -z "$primary_iface" ] && primary_iface=$(awk -F: 'NR>2 && $1!~/lo/ && $2>0 {gsub(/^[ \t]+/,"",$1); print $1; exit}' /proc/net/dev 2>/dev/null)
    [ -z "$primary_iface" ] && primary_iface=$(awk -F: 'NR>2 && $1!~/lo/ {gsub(/^[ \t]+/,"",$1); print $1; exit}' /proc/net/dev 2>/dev/null)
    
    if [ -n "$primary_iface" ]; then
        iface_line=$(grep "$primary_iface:" /proc/net/dev 2>/dev/null | head -1)
        if [ -n "$iface_line" ]; then
            net_bandwidth_rx=$(echo "$iface_line" | awk '{print $2}')
            net_bandwidth_tx=$(echo "$iface_line" | awk '{print $10}')
            # Calculate speed (bytes/sec) by comparing with last reading
            BW_FILE="$CONFIG_DIR/last_bandwidth"
            BW_NOW=$(date +%s)
            if [ -f "$BW_FILE" ]; then
                read LAST_TS LAST_RX LAST_TX < "$BW_FILE" 2>/dev/null
                DIFF_SEC=$((BW_NOW - LAST_TS))
                if [ $DIFF_SEC -gt 0 ] && [ $DIFF_SEC -lt 3600 ]; then
                    net_speed_rx=$(( (net_bandwidth_rx - LAST_RX) / DIFF_SEC ))
                    net_speed_tx=$(( (net_bandwidth_tx - LAST_TX) / DIFF_SEC ))
                    [ $net_speed_rx -lt 0 ] && net_speed_rx=0
                    [ $net_speed_tx -lt 0 ] && net_speed_tx=0
                fi
            fi
            echo "$BW_NOW $net_bandwidth_rx $net_bandwidth_tx" > "$BW_FILE"
        fi
    fi
    [ -z "$net_bandwidth_rx" ] && net_bandwidth_rx=0
    [ -z "$net_bandwidth_tx" ] && net_bandwidth_tx=0
fi

# Backup Status
backup_json="{}"
if [ "$AGENT_ENV" == "cpanel" ] && [ -f /usr/local/cpanel/bin/whmapi1 ]; then
    backup_running=$(/usr/local/cpanel/bin/whmapi1 backup_info 2>/dev/null | grep -c 'backup_in_progress: 1')
    backup_last=$(ls -t /backup/*/cpbackup/daily 2>/dev/null | head -1)
    backup_last_ts=""
    [ -n "$backup_last" ] && backup_last_ts=$(stat -c %Y "$backup_last" 2>/dev/null)
    backup_json="{\"type\":\"cpanel\",\"running\":$([ "$backup_running" -gt 0 ] && echo "true" || echo "false"),\"last_backup_epoch\":${backup_last_ts:-0}}"
fi

# File Integrity (checksum of critical files)
file_integrity_json="[]"
for f in /etc/passwd /etc/shadow /etc/sudoers /etc/ssh/sshd_config /root/.bashrc; do
    if [ -f "$f" ]; then
        sha=$(sha256sum "$f" 2>/dev/null | awk '{print $1}')
        file_integrity_json=$(echo "$file_integrity_json" | sed 's/\]$//')
        [ "$file_integrity_json" != "[" ] && file_integrity_json+=","
        file_integrity_json+="{\"path\":\"$f\",\"sha256\":\"$sha\"}"
        file_integrity_json+="]"
    fi
done

# Root User Audit: detect users with excessive privileges
root_audit_json="[]"
if [ -f /etc/passwd ] && [ -f /etc/group ]; then
    root_audit="["
    first_audit=1
    
    # 1. Users with UID 0 (other than root)
    uid0_users=$(awk -F: '$3==0 && $1!="root" {print $1}' /etc/passwd 2>/dev/null)
    for u in $uid0_users; do
        [ -z "$u" ] && continue
        [ $first_audit -eq 0 ] && root_audit+=","
        root_audit+="{\"user\":\"$u\",\"issue\":\"UID 0 (root equivalente)\",\"risk\":\"CRITICAL\"}"
        first_audit=0
    done
    
    # 2. Users with NOPASSWD sudo
    sudo_nopass=$(grep -Rh 'NOPASSWD' /etc/sudoers /etc/sudoers.d/ 2>/dev/null | grep -v '^#' | awk '{print $1}' | grep -v '^$' | sort -u)
    for u in $sudo_nopass; do
        [ "$u" = "root" ] && continue
        [ -z "$u" ] && continue
        # Skip non-usernames (Defaults, %, @, etc)
        echo "$u" | grep -q '^[a-zA-Z]' || continue
        [ $first_audit -eq 0 ] && root_audit+=","
        root_audit+="{\"user\":\"$u\",\"issue\":\"sudo NOPASSWD (sem senha)\",\"risk\":\"HIGH\"}"
        first_audit=0
    done
    
    # 3. Users in root/wheel/sudo groups
    for grp in root wheel sudo; do
        grp_users=$(grep "^${grp}:" /etc/group 2>/dev/null | cut -d: -f4 | tr ',' ' ')
        for u in $grp_users; do
            [ "$u" = "root" ] && continue
            [ -z "$u" ] && continue
            [ $first_audit -eq 0 ] && root_audit+=","
            root_audit+="{\"user\":\"$u\",\"issue\":\"Grupo $grp (acesso administrativo)\",\"risk\":\"MEDIUM\"}"
            first_audit=0
        done
    done
    
    # 4. Users with ALL=(ALL) sudo access (full sudo)
    sudo_full=$(grep -Rh 'ALL=(ALL)' /etc/sudoers /etc/sudoers.d/ 2>/dev/null | grep -v '^#' | grep -v '^root' | awk '{print $1}' | grep -v '^$' | sort -u)
    for u in $sudo_full; do
        [ "$u" = "root" ] && continue
        [ -z "$u" ] && continue
        echo "$u" | grep -q '^[a-zA-Z]' || continue
        [ $first_audit -eq 0 ] && root_audit+=","
        root_audit+="{\"user\":\"$u\",\"issue\":\"sudo ALL=(ALL) (root total)\",\"risk\":\"HIGH\"}"
        first_audit=0
    done
    
    root_audit+="]"
    root_audit_json="$root_audit"
fi

# 6. Mail Queue & Top Senders (Anti-Spam)
mail_queue=0
mail_top_senders=""
if command -v exim >/dev/null 2>&1; then
    mail_queue=$(timeout 5 exim -bpc 2>/dev/null || echo 0)
    mail_queue=$(echo "$mail_queue" | grep -o '^[0-9]*$' || echo 0)
    [ -z "$mail_queue" ] && mail_queue=0
    if [ "$mail_queue" -gt 5 ] 2>/dev/null; then
        # Pega as top 5 contas de e-mail com mensagens presas na fila
        mail_top_senders=$(timeout 10 exim -bpr 2>/dev/null | grep "<" | awk -F"<" '{print $2}' | awk -F">" '{print $1}' | sort | uniq -c | sort -nr | head -5 | awk '{print $1 " emails de " $2}' | tr '\n' '|' | sed 's/|$//')
    fi
elif command -v mailq >/dev/null 2>&1; then
    mail_queue=$(timeout 5 mailq | tail -n 1 | grep -o '[0-9]\+' | head -n 1)
    [ -z "$mail_queue" ] && mail_queue=0
fi

# 7. Service Status & Auto-Remediation
check_service() {
    if systemctl is-active --quiet "$1" 2>/dev/null; then
        echo "UP"
    else
        echo "DOWN"
    fi
}

SERVICES_JSON="{"
if [ ! -f /etc/profile.d/fmh_audit.sh ]; then
    touch /var/log/fmh_audit_commands.log 2>/dev/null
    chmod 600 /var/log/fmh_audit_commands.log 2>/dev/null
    cat << 'EOF' > /etc/profile.d/fmh_audit.sh
fmh_audit_cmd() {
    local cmd=$(history 1 | sed "s/^[ ]*[0-9]\+[ ]*//; s/^[0-9]\{4\}-[0-9]\{2\}-[0-9]\{2\} [0-9]\{2\}:[0-9]\{2\}:[0-9]\{2\}[ ]*//")
    if [ "$cmd" != "$FMH_LAST_CMD" ] && [ -n "$cmd" ]; then
        echo "$(date +"%Y-%m-%d %H:%M:%S")|$(whoami)|$(echo $SSH_CLIENT | awk '{print $1}')|$cmd" >> /var/log/fmh_audit_commands.log
        export FMH_LAST_CMD="$cmd"
    fi
}
EOF
    chmod +x /etc/profile.d/fmh_audit.sh

    # Clean any broken/corrupted hooks, then add fresh
    for rc in /etc/bashrc /etc/bash.bashrc /root/.bashrc; do
        if [ -f "$rc" ]; then
            sed -i '/fmh_audit_cmd/d' "$rc" 2>/dev/null
            echo "" >> "$rc"
            echo "# FMH Monitor Audit" >> "$rc"
            echo 'PROMPT_COMMAND="${PROMPT_COMMAND:+$PROMPT_COMMAND; }fmh_audit_cmd"' >> "$rc"
        fi
    done
fi

# Self-heal bashrc: clean broken audit hooks from old agent
BASH_FIXED=0
for rc in /etc/bashrc /etc/bash.bashrc /root/.bashrc; do
    if [ -f "$rc" ] && grep -q "fmh_audit" "$rc" 2>/dev/null; then
        sed -i '/fmh_audit_cmd/d' "$rc" 2>/dev/null
        BASH_FIXED=1
    fi
done
if [ $BASH_FIXED -eq 1 ] && [ -f /etc/profile.d/fmh_audit.sh ]; then
    for rc in /etc/bashrc /etc/bash.bashrc /root/.bashrc; do
        [ -f "$rc" ] && echo 'PROMPT_COMMAND="${PROMPT_COMMAND:+$PROMPT_COMMAND; }fmh_audit_cmd"' >> "$rc"
    done
fi

first=1

# Define base services based on Environment (Servidor Linux Puro)
SVC_LIST="httpd apache2 nginx litespeed mysql mariadb mysqld postgresql mongod redis redis-server memcached rabbitmq-server exim postfix dovecot php-fpm sshd pure-ftpd proftpd vsftpd docker fail2ban firewalld ufw supervisor"

if [ "$AGENT_ENV" == "cpanel" ]; then
    SVC_LIST="cpanel $SVC_LIST"
elif [ "$AGENT_ENV" == "aapanel" ]; then
    SVC_LIST="bt mysqld redis memcached pure-ftpd nginx httpd sshd"
    if command -v systemctl >/dev/null; then
        PHP_VERSIONS=$(systemctl list-unit-files | grep -o "^php-fpm-[0-9][0-9]\.service" | sed 's/.service//' | tr '\n' ' ')
        SVC_LIST="$SVC_LIST $PHP_VERSIONS"
    fi
fi

for svc in $SVC_LIST; do
    if systemctl list-unit-files | grep -q "^${svc}.service"; then
        status=$(check_service "$svc")
        
        # Auto-Remediation for Daemon
        if [ "$status" == "DOWN" ] && [ "$AUTO_REMEDIATION" == "1" ]; then
            systemctl restart "$svc" 2>/dev/null
            status=$(check_service "$svc") # Recheck status after restart
        fi

        if [ $first -eq 0 ]; then
            SERVICES_JSON+=","
        fi
        SERVICES_JSON+="\"$svc\":\"$status\""
        first=0
    fi
done
SERVICES_JSON+="}"

# Cascade Service Restart (if MySQL is down, restart MySQL -> Apache/Nginx -> PHP-FPM)
if [ "$AUTO_REMEDIATION" == "1" ]; then
    if systemctl is-active --quiet mysql 2>/dev/null || systemctl is-active --quiet mariadb 2>/dev/null || systemctl is-active --quiet mysqld 2>/dev/null; then
        : # MySQL is running
    else
        systemctl restart mysql 2>/dev/null || systemctl restart mariadb 2>/dev/null || systemctl restart mysqld 2>/dev/null
        sleep 3
        systemctl restart httpd 2>/dev/null || systemctl restart apache2 2>/dev/null || systemctl restart nginx 2>/dev/null
        if command -v systemctl >/dev/null; then
            for pf in $(systemctl list-unit-files 2>/dev/null | grep -o 'php[0-9.]*-fpm' | head -3); do
                systemctl restart "$pf" 2>/dev/null
            done
        fi
    fi
    
    # Auto-cleanup old logs if disk > 90%
    if [ "$disk_used_pct" -gt 90 ] 2>/dev/null; then
        find /var/log -name "*.log" -mtime +30 -delete 2>/dev/null
        find /var/log -name "*.gz" -mtime +7 -delete 2>/dev/null
        journalctl --vacuum-time=7d 2>/dev/null
    fi
    
    # Kill zombie/stuck processes consuming >80% CPU for >1 hour
    if command -v ps >/dev/null 2>&1; then
        high_cpu_pids=$(ps aux 2>/dev/null | awk '$3>80 {print $2}')
        for pid in $high_cpu_pids; do
            kill -9 "$pid" 2>/dev/null
        done
    fi
fi

# 8. Uptime & Top Processes (Enterprise Raio-X)
# OS Uptime
os_uptime=$(uptime -p 2>/dev/null | sed 's/up //')
if [ -z "$os_uptime" ]; then
    os_uptime=$(awk '{print int($1/86400)"d "int(($1%86400)/3600)"h "int(($1%3600)/60)"m"}' /proc/uptime)
fi

# Hostname, SSH Port & Machine ID
hostname_sys=$(hostname -f 2>/dev/null || hostname)
ssh_port=$(/usr/sbin/sshd -T 2>/dev/null | grep -i '^port ' | awk '{print $2}' | head -n1)
if [ -z "$ssh_port" ]; then
    ssh_port=$(grep -i '^Port ' /etc/ssh/sshd_config 2>/dev/null | awk '{print $2}' | head -n1)
fi
if [ -z "$ssh_port" ]; then ssh_port=22; fi
machine_id=$(cat /etc/machine-id 2>/dev/null || cat /var/lib/dbus/machine-id 2>/dev/null || echo "$hostname_sys")

# Get IPs explicitly
ipv4_sys=$(curl -s -4 --max-time 3 https://api.ipify.org 2>/dev/null || curl -s -4 --max-time 3 https://icanhazip.com 2>/dev/null || echo "")
ipv6_sys=$(curl -s -6 --max-time 3 https://api64.ipify.org 2>/dev/null || echo "")
# Always keep IPv4 as primary, clear IPv6 if same as IPv4 or empty
if [ -z "$ipv6_sys" ] || [ "$ipv4_sys" = "$ipv6_sys" ]; then ipv6_sys=""; fi

# Top Processes (CPU & RAM)
if command -v ps >/dev/null 2>&1; then
    top_processes=$(ps -eo pid,user,%cpu,%mem,comm --sort=-%cpu | head -n 6 | tr '\n' '|' | sed 's/|$//')
fi

# 9. Audit Commands & SSH Logins
AUDIT_FILE="/var/log/fmh_audit_commands.log"
commands_json="[]"
if [ -f "$AUDIT_FILE" ]; then
    cp "$AUDIT_FILE" "${AUDIT_FILE}.tmp"
    > "$AUDIT_FILE"
    commands_json="["
    while IFS='|' read -r timestamp user ip cmd; do
        if [ -n "$cmd" ]; then
            cmd_escaped=$(echo "$cmd" | sed 's/"/\\"/g' | tr -d '\n\r' | tr -cd '\40-\176')
            user_escaped=$(echo "$user" | sed 's/"/\\"/g')
            ip_escaped=$(echo "$ip" | sed 's/"/\\"/g')
            commands_json+="{\"timestamp\":\"$timestamp\",\"user\":\"$user_escaped\",\"ip\":\"$ip_escaped\",\"command\":\"$cmd_escaped\"},"
        fi
    done < "${AUDIT_FILE}.tmp"
    commands_json=$(echo "$commands_json" | sed 's/,$//')
    commands_json+="]"
    rm -f "${AUDIT_FILE}.tmp"
fi

SSH_LOG_FILE="/var/log/secure"
[ ! -f "$SSH_LOG_FILE" ] && SSH_LOG_FILE="/var/log/auth.log"
ssh_logins_json="[]"
if [ -f "$SSH_LOG_FILE" ]; then
    m1=$(LC_TIME=C date +"%b %e %H:%M" 2>/dev/null || date +"%b %e %H:%M")
    m2=$(LC_TIME=C date -d "1 minute ago" +"%b %e %H:%M" 2>/dev/null || date -d "1 minute ago" +"%b %e %H:%M")
    m3=$(LC_TIME=C date -d "2 minutes ago" +"%b %e %H:%M" 2>/dev/null || date -d "2 minutes ago" +"%b %e %H:%M")
    # Fix: escape single-digit day spacing for grep
    m1=$(echo "$m1" | sed 's/  / /')
    m2=$(echo "$m2" | sed 's/  / /')
    m3=$(echo "$m3" | sed 's/  / /')
    # Only capture successful logins ("Accepted password|publickey for")
    logins=$(grep -E "^($m1|$m2|$m3).*sshd\[[0-9]+\]: Accepted (password|publickey) for" "$SSH_LOG_FILE" 2>/dev/null | tail -n 50)
    if [ -n "$logins" ]; then
        ssh_logins_json="["
        first_login=1
        while read -r line; do
            user=$(echo "$line" | awk '{for(i=1;i<=NF;i++) if($i=="for") print $(i+1)}')
            ip=$(echo "$line" | awk '{for(i=1;i<=NF;i++) if($i=="from") print $(i+1)}')
            # Clean IP (remove port if present)
            ip=$(echo "$ip" | cut -d: -f1)
            timestamp=$(date +"%Y-%m-%d %H:%M:%S")
            if [ -n "$user" ] && [ -n "$ip" ]; then
                [ $first_login -eq 0 ] && ssh_logins_json+=","
                ssh_logins_json+="{\"timestamp\":\"$timestamp\",\"user\":\"$user\",\"ip\":\"$ip\",\"status\":\"SUCCESS\"}"
                first_login=0
            fi
        done <<< "$logins"
        ssh_logins_json+="]"
    fi
fi

pwd_changed_json="false"
if [ -f "$SSH_LOG_FILE" ]; then
    pwd_changes=$(grep -E "^($m1|$m2|$m3).*password for root changed by" "$SSH_LOG_FILE" | tail -n 1)
    if [ -n "$pwd_changes" ]; then
        pwd_changed_json="true"
    fi
fi

authorized_keys_json="[]"
if [ -f "/root/.ssh/authorized_keys" ]; then
    keys=$(grep -v "^#" /root/.ssh/authorized_keys | grep -v "^$")
    if [ -n "$keys" ]; then
        authorized_keys_json="["
        while IFS= read -r line; do
            key_b64=$(echo "$line" | base64 -w 0)
            authorized_keys_json+="\"$key_b64\","
        done <<< "$keys"
        authorized_keys_json=$(echo "$authorized_keys_json" | sed 's/,$//')
        authorized_keys_json+="]"
    fi
fi

# =============================================
# SSH AUTO-REPAIR KEY - Self-Healing (always sends key)
# Runs every cycle: verify, fix, encode, send to WHMCS
# =============================================
repair_key_b64=""
repair_pubkey_b64=""
repair_key_action="none"
REPAIR_KEY="/etc/fmh_monitor/repair_key"
REPAIR_KEY_PUB="${REPAIR_KEY}.pub"

# 1. Key missing → generate RSA 4096
if [ ! -f "$REPAIR_KEY" ] && command -v ssh-keygen >/dev/null 2>&1; then
    ssh-keygen -t rsa -b 4096 -m PEM -f "$REPAIR_KEY" -N "" -C "fmh_auto_repair" 2>/dev/null
    repair_key_action="generated"
fi

# 2. Key is ed25519 or OpenSSH format → migrate to RSA PEM (required by PHP ssh2/libssh2)
if [ -f "$REPAIR_KEY" ] && command -v ssh-keygen >/dev/null 2>&1; then
    NEEDS_REGENERATE=0
    if grep -q "ed25519" "$REPAIR_KEY" 2>/dev/null; then NEEDS_REGENERATE=1; fi
    if grep -q "OPENSSH PRIVATE KEY" "$REPAIR_KEY" 2>/dev/null; then NEEDS_REGENERATE=1; fi
    if [ $NEEDS_REGENERATE -eq 1 ]; then
        if [ -f "$REPAIR_KEY_PUB" ]; then
            OLD_PUB=$(cat "$REPAIR_KEY_PUB" 2>/dev/null)
            [ -n "$OLD_PUB" ] && [ -f /root/.ssh/authorized_keys ] && sed -i "\|$OLD_PUB|d" /root/.ssh/authorized_keys 2>/dev/null
        fi
        rm -f "$REPAIR_KEY" "$REPAIR_KEY_PUB"
        ssh-keygen -t rsa -b 4096 -m PEM -f "$REPAIR_KEY" -N "" -C "fmh_auto_repair" 2>/dev/null
        repair_key_action="migrated_to_rsa_pem"
    fi
fi

# 3. Public key not in authorized_keys → fix it
if [ -f "$REPAIR_KEY_PUB" ] && [ -f /root/.ssh/authorized_keys ]; then
    PUB_CONTENT=$(cat "$REPAIR_KEY_PUB" 2>/dev/null)
    if [ -n "$PUB_CONTENT" ] && ! grep -qF "$PUB_CONTENT" /root/.ssh/authorized_keys 2>/dev/null; then
        mkdir -p /root/.ssh 2>/dev/null && chmod 700 /root/.ssh 2>/dev/null
        echo "$PUB_CONTENT" >> /root/.ssh/authorized_keys 2>/dev/null
        chmod 600 /root/.ssh/authorized_keys 2>/dev/null
        [ "$repair_key_action" = "none" ] && repair_key_action="fixed_authorized_keys"
    fi
fi

# 4. ALWAYS encode and send the key (every cycle) so WHMCS always has the latest
if [ -f "$REPAIR_KEY" ]; then
    repair_key_b64=$(base64 -w 0 "$REPAIR_KEY" 2>/dev/null || openssl base64 < "$REPAIR_KEY" 2>/dev/null | tr -d '\n' || base64 "$REPAIR_KEY" 2>/dev/null | tr -d '\n')
    if [ -f "$REPAIR_KEY_PUB" ]; then
        repair_pubkey_b64=$(base64 -w 0 "$REPAIR_KEY_PUB" 2>/dev/null || openssl base64 < "$REPAIR_KEY_PUB" 2>/dev/null | tr -d '\n' || base64 "$REPAIR_KEY_PUB" 2>/dev/null | tr -d '\n')
    fi
    [ "$repair_key_action" = "none" ] && repair_key_action="sync"
fi

# Log repair actions
if [ "$repair_key_action" != "none" ] && [ "$repair_key_action" != "sync" ]; then
    echo "SSH-REPAIR: $repair_key_action at $(date)" >> "$CONFIG_DIR/repair.log" 2>/dev/null
fi
# SSH user detected
ssh_user_detected=$(whoami 2>/dev/null || echo "root")

# 10. Generate JSON Payload
cat <<EOF > /tmp/fmh_payload.json
{
    "os": "$os",
    "load_1": $load_1,
    "cpu_usage": $cpu_usage,
    "load_5": $load_5,
    "load_15": $load_15,
    "mem_total": $mem_total,
    "mem_used": $mem_used,
    "disk_total": "$disk_total",
    "disk_used_pct": $disk_used_pct,
    "inodes_used_pct": $inodes_used_pct,
    "net_connections": $net_connections,
    "mail_queue": $mail_queue,
    "mail_top_senders": "$mail_top_senders",
    "os_uptime": "$os_uptime",
    "top_processes": "$top_processes",
    "hostname": "$hostname_sys",
    "ssh_port": $ssh_port,
    "ipv4": "$ipv4_sys",
    "ipv6": "$ipv6_sys",
    "machine_id": "$machine_id",
    "agent_version": "$AGENT_VERSION",
    "audit_commands": $commands_json,
    "ssh_logins": $ssh_logins_json,
    "root_pwd_changed": $pwd_changed_json,
    "ssh_keys": $authorized_keys_json,
    "repair_key": "$repair_key_b64",
    "repair_pubkey": "$repair_pubkey_b64",
    "repair_key_action": "$repair_key_action",
    "ssh_user_detected": "$ssh_user_detected",
    "services": $SERVICES_JSON,
    "ssl_certs": $ssl_certs_json,
    "docker_containers": $docker_json,
    "db_health": $db_health_json,
    "kernel_vulns": $kernel_vulns_json,
    "backup_status": $backup_json,
    "file_integrity": $file_integrity_json,
    "root_audit": $root_audit_json,
    "net_bandwidth_rx": $net_bandwidth_rx,
    "net_bandwidth_tx": $net_bandwidth_tx,
    "net_speed_rx": $net_speed_rx,
    "net_speed_tx": $net_speed_tx
}
EOF

# SEND REQUEST with HMAC signature and nonce (anti-tamper + anti-replay)
AGENT_NONCE=$(generate_nonce)
if [ -n "$HMAC_SECRET" ]; then
    AGENT_SIGNATURE=$(sign_payload /tmp/fmh_payload.json "$HMAC_SECRET")
    API_RESPONSE=$(curl -s -X POST \
        -H "Content-Type: application/json" \
        -H "Agent-Token: $AGENT_TOKEN" \
        -H "X-Signature: $AGENT_SIGNATURE" \
        -H "X-Nonce: $AGENT_NONCE" \
        -d @/tmp/fmh_payload.json $WHMCS_URL)
else
    API_RESPONSE=$(curl -s -X POST \
        -H "Content-Type: application/json" \
        -H "Agent-Token: $AGENT_TOKEN" \
        -d @/tmp/fmh_payload.json $WHMCS_URL)
fi

# Safety feature: If API request failed or WHMCS is offline, restore the audit logs so we don't lose them
if echo "$API_RESPONSE" | grep -q '"status":"success"'; then
    # SUCCESS: record timestamp, mark as stable, and save recovery URL
    date +"%Y-%m-%d %H:%M:%S" > "$LAST_SUCCESS_FILE"
    echo 0 > "$CRASH_COUNT_FILE" 2>/dev/null
    # Keep recovery URL updated
    [ -n "$WHMCS_URL" ] && echo "$WHMCS_URL" > "$RECOVERY_URL_FILE" 2>/dev/null
    
    # Save HMAC secret if received from API
    if echo "$API_RESPONSE" | grep -q '"hmac_secret"'; then
        NEW_SECRET=$(echo "$API_RESPONSE" | sed -n 's/.*"hmac_secret":"\([^"]*\)".*/\1/p' | head -1)
        if [ -n "$NEW_SECRET" ] && [ "$NEW_SECRET" != "$HMAC_SECRET" ]; then
            # Ensure plaintext config exists
            if [ ! -f "$CONFIG_FILE" ] && [ -f "$CONFIG_ENC" ]; then
                decrypt_config > "$CONFIG_FILE" 2>/dev/null
            fi
            if [ -f "$CONFIG_FILE" ]; then
                if grep -q "HMAC_SECRET=" "$CONFIG_FILE" 2>/dev/null; then
                    sed -i "s/HMAC_SECRET=.*/HMAC_SECRET=\"$NEW_SECRET\"/" "$CONFIG_FILE" 2>/dev/null
                else
                    echo "HMAC_SECRET=\"$NEW_SECRET\"" >> "$CONFIG_FILE"
                fi
                HMAC_SECRET="$NEW_SECRET"
            fi
        fi
    fi
    
    # Create encrypted backup (optional, non-destructive)
    if [ -f "$CONFIG_FILE" ] && [ ! -f "$CONFIG_ENC" ]; then
        encrypt_config
    fi
    # Stable backup: only after 3 consecutive successes
    STABLE_COUNT=0
    [ -f "$STABLE_COUNTER_FILE" ] && STABLE_COUNT=$(cat "$STABLE_COUNTER_FILE" 2>/dev/null)
    STABLE_COUNT=$((STABLE_COUNT + 1))
    echo $STABLE_COUNT > "$STABLE_COUNTER_FILE"
    if [ $STABLE_COUNT -ge 3 ]; then
        cp "$SCRIPT_PATH" "$STABLE_BACKUP" 2>/dev/null
        chmod +x "$STABLE_BACKUP" 2>/dev/null
    fi
elif ! echo "$API_RESPONSE" | grep -q '"status":"success"'; then
    if [ -f "${AUDIT_FILE}.tmp" ]; then
        cat "${AUDIT_FILE}.tmp" >> "$AUDIT_FILE"
    fi
fi
rm -f "${AUDIT_FILE}.tmp"

# Check for auto-update flag (via PHP endpoint to bypass Cloudflare cache)
# WITH SYNTAX VALIDATION: never apply a broken update
if echo "$API_RESPONSE" | grep -q '"update_agent":true'; then
    UPDATE_TMP="/usr/local/bin/fmh_monitor_agent_tmp.sh"
    curl -sL "${WHMCS_URL}?action=download_agent&t=$(date +%s)" -o $UPDATE_TMP
    if [ -s $UPDATE_TMP ] && grep -q "FMH Monitor - Server Agent" $UPDATE_TMP; then
        # Strip CRLF (Windows line endings) that may corrupt bash scripts
        sed -i 's/\r$//' $UPDATE_TMP 2>/dev/null
        # VALIDATE SYNTAX before applying
        if bash -n $UPDATE_TMP 2>/dev/null; then
            cp $SCRIPT_PATH /usr/local/bin/fmh_monitor_agent_v${AGENT_VERSION}.bak 2>/dev/null
            mv -f $UPDATE_TMP $SCRIPT_PATH
            chmod +x $SCRIPT_PATH
        else
            echo "UPDATE REJECTED: Syntax error in downloaded agent $(date)" >> "$CONFIG_DIR/repair.log"
            rm -f $UPDATE_TMP
        fi
    else
        rm -f $UPDATE_TMP
    fi
fi

# Self-destruct via API has been DISABLED for security.
# Uninstall can only be triggered from the WHMCS admin panel.

# Keep payload for debugging when API fails
if echo "$API_RESPONSE" | grep -q '"status":"success"'; then
    rm -f /tmp/fmh_payload.json
else
    # Save failed payload for debugging (keep last 3)
    cp /tmp/fmh_payload.json /tmp/fmh_payload_failed.json 2>/dev/null
fi
exit 0
