# Falhen Media - SSH & Deployment Details

This document contains SSH connection details and quick commands for remote access and deployment to the live server.

---

## 🔑 SSH Credentials & Connection Details

- **Server Host:** `[PROTECTED_VIA_CLOUDFLARE_ENV]` (Set `FALHEN_SSH_HOST` in local environment; never commit raw origin IP)
- **Domain:** `falhen.com` (Proxied via Cloudflare Edge)
- **SSH Port:** `21098`
- **Username:** `henadinnovate`
- **SSH Key Location:** `~/.ssh/falhen-antigravity`
- **Remote Web Root Directory:** `/home/henadinnovate/falhen.com`

---

## 🛠️ Quick SSH Commands

### Connect to Server via SSH
```bash
ssh -i "~/.ssh/falhen-antigravity" -p 21098 henadinnovate@$FALHEN_SSH_HOST
```

### Check Live Error Logs
```bash
ssh -i "~/.ssh/falhen-antigravity" -p 21098 henadinnovate@$FALHEN_SSH_HOST "tail -n 50 /home/henadinnovate/falhen.com/error_log"
```

### Sync/Upload a Single File (e.g. `index.php`)
```bash
scp -P 21098 -i "~/.ssh/falhen-antigravity" index.php henadinnovate@$FALHEN_SSH_HOST:/home/henadinnovate/falhen.com/index.php
```

### Sync Entire Project via Rsync
```bash
rsync -avz -e "ssh -p 21098 -i ~/.ssh/falhen-antigravity" --exclude '.git' --exclude 'scratch/' ./ henadinnovate@$FALHEN_SSH_HOST:/home/henadinnovate/falhen.com/
```
