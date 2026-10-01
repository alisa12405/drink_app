# Trien khai `drinks.hmmh.click` qua Cloudflare Tunnel

Kien truc trien khai:

```text
Internet -> Cloudflare -> cloudflared -> site (Nginx)
                                      |-> /       Vue static build
                                      `-> /api/*  Laravel Nginx/PHP-FPM
```

Cloudflare Tunnel chi tao ket noi outbound tu may chay Docker. Khong can mo port
80/443 tren router, va khong tro A record vao IP nha/may chu.

## 1. Tao tunnel va public hostname

1. Dam bao domain `hmmh.click` dang dung nameserver cua Cloudflare.
2. Vao Cloudflare dashboard -> **Networking -> Tunnels** -> tao tunnel moi, vi du
   `drink-app`.
3. Chon cach cai dat Docker va sao chep token trong lenh mau. Chi lay chuoi token
   sau `--token`; khong commit token vao Git.
4. Trong tunnel, them **Published application**:
   - Subdomain: `drinks`
   - Domain: `hmmh.click`
   - Type: `HTTP`
   - Service URL: `http://site:80`

Cloudflare se tao CNAME cho `drinks.hmmh.click`. Neu dashboard bao ban ghi da ton
tai, xoa ban ghi A/AAAA/CNAME cu cua chinh `drinks` roi them Published application
lai. Khong xoa cac ban ghi khac cua domain.

## 2. Cau hinh may chay Docker

Trong file `.env` o root project, them/cap nhat:

```dotenv
APP_URL=https://drinks.hmmh.click
SITE_HTTP_PORT=8090
CLOUDFLARE_TUNNEL_TOKEN=<token-cua-tunnel>
```

File `.env` da nam trong `.gitignore`. Khong dat token that vao `.env.example`,
Dockerfile, anh chup man hinh hay commit.

## 3. Build va khoi dong

```bash
make deploy-cloudflare
```

Neu may khong co `make`:

```bash
docker compose --profile cloudflare up -d --build site cloudflared
```

Kiem tra local production build tai `http://localhost:8090`, sau do xem trang thai:

```bash
docker compose ps
docker compose logs -f site cloudflared
```

Khi tunnel bao `Healthy`, truy cap `https://drinks.hmmh.click`.

## 4. Xu ly loi nhanh

- Cloudflare `1016`/route khong vao duoc app: kiem tra Service URL phai la
  `http://site:80`, khong phai URL public va khong phai `localhost`.
- Tunnel restart lien tuc: token trong `.env` dang thieu/sai/da bi rotate.
- Cloudflare khong cho them hostname: `drinks` dang co A/AAAA/CNAME cu.
- Trang hien nhung API loi: chay `docker compose logs app webserver site` va kiem
  tra `APP_URL=https://drinks.hmmh.click`.
- Chi de public service `site`; khong tao Published application cho MySQL, Redis
  hoac phpMyAdmin.
