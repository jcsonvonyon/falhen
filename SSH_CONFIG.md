# Falhen Media - SSH & Deployment Details

This document contains SSH connection details and quick commands for remote access and deployment to the live server.

---

## 🔑 SSH Credentials & Connection Details

- **Server IP:** `63.250.38.13`
- **Domain:** `falhen.com`
- **SSH Port:** `21098`
- **Username:** `henadinnovate`
- **SSH Key Location:** `~/.ssh/falhen-antigravity` (`C:\Users\USER\.ssh\falhen-antigravity`)
- **Remote Web Root Directory:** `/home/henadinnovate/falhen.com`

---

## 🛠️ Quick SSH Commands

### Connect to Server via SSH
```bash
ssh -i "~/.ssh/falhen-antigravity" -p 21098 henadinnovate@63.250.38.13
```

### Check Live Error Logs
```bash
ssh -i "~/.ssh/falhen-antigravity" -p 21098 henadinnovate@63.250.38.13 "tail -n 50 /home/henadinnovate/falhen.com/error_log"
```

### Sync/Upload a Single File (e.g. `index.php`)
```bash
scp -P 21098 -i "~/.ssh/falhen-antigravity" index.php henadinnovate@63.250.38.13:/home/henadinnovate/falhen.com/index.php
```

### Sync Entire Project via Rsync
```bash
rsync -avz -e "ssh -p 21098 -i ~/.ssh/falhen-antigravity" --exclude '.git' --exclude 'scratch/' ./ henadinnovate@63.250.38.13:/home/henadinnovate/falhen.com/
```
