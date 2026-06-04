# Servidor Nominatim — Entre Ríos

Servidor de geocodificación [Nominatim](https://nominatim.org/) corriendo sobre el extract
`entre_rios.osm.pbf` (provincia de Entre Ríos, Argentina).

> **Estado:** operativo ✅ — instalado el 2026-06-04.

---

## 1. Arquitectura

Windows Server 2019 no soporta WSL2 ni Docker Desktop, por lo que Nominatim corre dentro de
una VM Linux gestionada por Hyper-V:

```
Windows Server 2019 (host físico Dell PowerEdge R420)
        │
        ├─ Hyper-V
        │      └─ VM "Nominatim"  (Ubuntu 22.04 Server, Gen1, 8 GB RAM, 4 vCPU)
        │             └─ Docker Engine
        │                    └─ contenedor "nominatim"  (mediagis/nominatim:4.5)
        │                           └─ PostgreSQL 16 + PostGIS + API (puerto 8080)
        │
        └─ netsh portproxy  (localhost:8088 → 192.168.100.10:8080)
```

### Red

| Elemento | Valor |
|---|---|
| vSwitch Hyper-V | `NominatimNAT` (Internal) + `New-NetNat` |
| Subred | `192.168.100.0/24` |
| Host (vEthernet) | `192.168.100.1` |
| VM | `192.168.100.10/24`, gw `.1`, DNS `8.8.8.8` |
| NIC física del host (`NIC1`) | `193.169.1.246/24` — **no se tocó** |

> Se eligió un switch **Internal + NAT** a propósito, para no interrumpir la única NIC física
> del servidor (y por lo tanto la sesión remota).

---

## 2. Cómo acceder

| Desde | URL base |
|---|---|
| **Host Windows / LAN** | `http://localhost:8088` |
| Dentro de la VM | `http://192.168.100.10:8080` |

> ⚠️ El puerto **8080 del host** ya estaba ocupado por un proceso `python.exe` preexistente
> (PID 4048), por eso el reenvío del host quedó en **8088**.

### Ejemplos de uso

Búsqueda (geocoding):
```
http://localhost:8088/search?q=Concordia&format=jsonv2
http://localhost:8088/search?q=Victoria,+Entre+Rios&format=jsonv2&limit=1
```

Geocodificación inversa (reverse):
```
http://localhost:8088/reverse?lat=-31.7333&lon=-60.5238&format=jsonv2
```

Estado del servicio:
```
http://localhost:8088/status      →  OK
```

Detalle de un lugar:
```
http://localhost:8088/details?osmtype=R&osmid=10752043&format=json
```

### Acceso SSH a la VM

```powershell
ssh -i C:\Hyperv\Nominatim\nominatim_ed25519 nominatim@192.168.100.10
```

- Usuario: `nominatim` (sudo sin password)
- Password de consola (fallback): `nominatim`
- Autenticación principal: clave SSH `C:\Hyperv\Nominatim\nominatim_ed25519`

---

## 3. Detalles del contenedor

| Parámetro | Valor |
|---|---|
| Imagen | `mediagis/nominatim:4.5` |
| Nombre | `nominatim` |
| Reinicio | `--restart unless-stopped` |
| Puerto | `8080` (publicado en la VM) |
| PBF dentro del contenedor | `/nominatim/data.osm.pbf` |
| PBF en la VM | `/home/nominatim/data/entre_rios.osm.pbf` |
| Password BD (`NOMINATIM_PASSWORD`) | `nominatim_secret` |
| Memoria compartida | `--shm-size=1g` |

Comando con el que se levantó (ejecutado por el usuario `nominatim` dentro de la VM):

```bash
docker run -d --name nominatim --restart unless-stopped \
  -e PBF_PATH=/nominatim/data.osm.pbf \
  -e REPLICATION_URL= \
  -e NOMINATIM_PASSWORD=nominatim_secret \
  -e IMPORT_WIKIPEDIA=false \
  -v /home/nominatim/data/entre_rios.osm.pbf:/nominatim/data.osm.pbf \
  -p 8080:8080 \
  --shm-size=1g \
  mediagis/nominatim:4.5
```

> ⚠️ El PBF se monta **sin** `:ro`. La imagen hace `chown` sobre el archivo al iniciar; si se
> monta de solo lectura, el contenedor entra en *crash-loop*.

---

## 4. Operación

### Ver estado y logs
```powershell
# desde el host
ssh -i C:\Hyperv\Nominatim\nominatim_ed25519 nominatim@192.168.100.10 "docker ps"
ssh -i C:\Hyperv\Nominatim\nominatim_ed25519 nominatim@192.168.100.10 "docker logs --tail 50 nominatim"
```

### Reiniciar / parar / arrancar el servicio
```bash
docker restart nominatim
docker stop nominatim
docker start nominatim
```

### Gestionar la VM (desde el host, PowerShell admin)
```powershell
Get-VM -Name Nominatim
Start-VM -Name Nominatim
Stop-VM  -Name Nominatim          # apagado ordenado
Get-VM -Name Nominatim | Select-Object State, CPUUsage, MemoryAssigned, Uptime
```

### Actualizar los datos (nuevo .pbf)
```powershell
# 1) copiar el nuevo extract a la VM
scp -i C:\Hyperv\Nominatim\nominatim_ed25519 `
    C:\ruta\nuevo.osm.pbf `
    nominatim@192.168.100.10:/home/nominatim/data/entre_rios.osm.pbf

# 2) recrear el contenedor (reimporta los datos)
ssh -i C:\Hyperv\Nominatim\nominatim_ed25519 nominatim@192.168.100.10 `
    "docker rm -f nominatim"
# 3) volver a ejecutar el `docker run` de la sección 3
```

---

## 5. Archivos en el host

| Ruta | Qué es |
|---|---|
| `C:\NOMINATIM\entre_rios.osm.pbf` | Extract original (origen) |
| `C:\NOMINATIM\README.md` | Este documento |
| `C:\Hyperv\Nominatim\nominatim-os.vhdx` | Disco de la VM (40 GB dinámico) |
| `C:\Hyperv\Nominatim\seed.vhdx` | Seed cloud-init (CIDATA) |
| `C:\Hyperv\Nominatim\nominatim_ed25519[.pub]` | Clave SSH de acceso a la VM |
| `C:\Hyperv\Nominatim\seed\` | Fuentes cloud-init (user-data, meta-data, network-config) |
| `C:\Hyperv\Nominatim\qemu-img\` | qemu-img portable (conversión qcow2→vhdx) |

### Reenvío de puerto del host
```powershell
# ver
netsh interface portproxy show v4tov4
# recrear si hiciera falta
netsh interface portproxy add v4tov4 listenport=8088 listenaddress=0.0.0.0 `
      connectport=8080 connectaddress=192.168.100.10
```
Regla de firewall: **"Nominatim 8088"** (TCP entrante, puerto 8088).

---

## 6. Verificación realizada

| Prueba | Resultado |
|---|---|
| `/status` | `OK` |
| Búsqueda "Paraná" | Ciudad de Paraná (relation 2879942) |
| Búsqueda "Gualeguaychú" | Ciudad de Gualeguaychú (relation 10752043) |
| Búsqueda "Victoria, Entre Ríos" | Localidad de Victoria (relation 3947363) |
| Reverse `-31.7333, -60.5238` | "La Rioja 118, Paraná, 3100, Argentina" |
| Acceso desde host (`localhost:8088`) | OK |

---

## 7. Integración con la app (Laravel)

El dashboard consume esta instancia vía `config/services.php` → clave `nominatim`.
Variables de entorno (definidas en el `.env` del servidor):

| Variable | Valor (self-hosted) | Para qué |
|---|---|---|
| `NOMINATIM_BASE_URL` | `http://193.169.1.246:8088` | URL base de la API |
| `NOMINATIM_DELAY_MS` | `100` | Pausa entre llamadas. Self-hosted no tiene el límite de 1 req/seg del público |
| `NOMINATIM_CONTEXTO` | `, Paraná` | Sufijo que se agrega a la dirección. ⚠️ Con `, Entre Ríos, Argentina` el extract devuelve `[]` |
| `NOMINATIM_REVERSE_BATCH_MAX` | `50` | Tope de reverse-geocodes nuevos por request |

> ⚠️ **Importante:** este extract provincial NO resuelve si a la dirección se le agrega
> `, Entre Ríos, Argentina`. Por eso `NOMINATIM_CONTEXTO` usa solo `, Paraná`. El bounding box
> del Gran Paraná en `GeocodificacionService` descarta los falsos positivos lejanos.

Para volver al servidor público de OSM: `NOMINATIM_BASE_URL=https://nominatim.openstreetmap.org`,
`NOMINATIM_DELAY_MS=1100` y `NOMINATIM_CONTEXTO=", Paraná, Entre Ríos, Argentina"`.

Tras cambiar cualquiera de estas variables: `php artisan config:clear`.

---

## 8. Notas / troubleshooting

- **404 desde `localhost:8080`** → el 8080 del host está tomado por otro proceso (python PID 4048).
  Usar el **8088**.
- **Contenedor en `Restarting`** → casi siempre el montaje `:ro` del PBF. Quitar `:ro`.
- **No responde la API justo tras crear el contenedor** → el import inicial tarda unos minutos;
  la API recién responde cuando termina la indexación. Seguir con `docker logs -f nominatim`.
- **La VM no levanta red** → verificar el vSwitch `NominatimNAT`, el `New-NetNat` y la IP
  `192.168.100.1` en el adaptador `vEthernet (NominatimNAT)` del host.
