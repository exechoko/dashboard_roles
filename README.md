# Nombre del Proyecto
- Control y Administración de Recursos 911 (C.A.R. 911)

## Requisitos
- PHP 7.4+, Composer, MySQL

## Instalación
1. `composer install`
2. Copiar `.env.example` a `.env`
3. `php artisan key:generate`
4. Configurar base de datos en `.env`
5. `php artisan migrate --seed`

## Estructura clave
- `app/Models`: Entidades del sistema
- `app/Http/Controllers`: Lógica de endpoints
- `routes/api.php`: Rutas de la API

## 📑 Listado de Rutas Principales
### 🔐 Autenticación
| Método    | Ruta               | Descripción                          |
|-----------|--------------------|--------------------------------------|
| GET       | /login            | Formulario de inicio de sesión       |
| POST      | /login            | Procesar inicio de sesión            |
| POST      | /logout           | Cerrar sesión                        |
| GET       | /register         | Formulario de registro               |
| POST      | /register         | Procesar registro                    |

### 👥 Usuarios y Roles
| Método    | Ruta                   | Descripción                      | Permisos Requeridos              |
|-----------|------------------------|----------------------------------|-----------------------------------|
| GET       | /usuarios             | Listar usuarios                 | ver-usuario                       |
| POST      | /usuarios             | Crear usuario                   | crear-usuario                     |
| GET       | /roles                | Listar roles                    | ver-rol                           |

### 🖥️ Dashboard y Reportes
| Método    | Ruta                                   | Descripción                                      |
|-----------|----------------------------------------|--------------------------------------------------|
| POST      | /get-equipos-funcionales-json         | Obtener equipos funcionales (JSON)              |
| POST      | /get-moviles-json                     | Obtener móviles (JSON)                          |
| GET       | /export-equipos                       | Exportar equipos a Excel                        |

### 🗃️ CRUD Principal
#### 📷 Cámaras
| Método    | Ruta                   | Descripción              | Permisos Requeridos              |
|-----------|------------------------|--------------------------|-----------------------------------|
| GET       | /camaras              | Listar cámaras          | ver-camara                        |
| POST      | /camaras              | Crear cámara            | crear-camara                      |
| GET       | /export-camaras       | Exportar a Excel        | ver-camara                        |

#### 🚔 Flota Policial
| Método    | Ruta                   | Descripción              | Permisos Requeridos              |
|-----------|------------------------|--------------------------|-----------------------------------|
| GET       | /flota                | Listar vehículos        | ver-flota                         |
| POST      | /flota                | Crear vehículo          | crear-flota                       |
| GET       | /busqueda-avanzada    | Búsqueda avanzada       | ver-flota                         |

#### 🏢 Dependencias
| Método    | Ruta                   | Descripción              | Permisos Requeridos              |
|-----------|------------------------|--------------------------|-----------------------------------|
| GET       | /dependencias         | Listar dependencias     | ver-dependencia                   |
| POST      | /dependencias         | Crear dependencia       | crear-dependencia                 |

### 📍 CECOCO (Centro de Comando y Control)
| Método    | Ruta                       | Descripción                      |
|-----------|----------------------------|----------------------------------|
| GET       | /indexMapaCecocoEnVivo    | Mapa en tiempo real             |
| POST      | /get-moviles-parados      | Obtener móviles detenidos       |
| GET       | /getRecursosCecoco        | Recursos disponibles            |

### 📄 Documentación Generada
| Método    | Ruta                   | Descripción                      |
|-----------|------------------------|----------------------------------|
| GET       | /generate-docx/{id}   | Generar reporte en Word         |
| GET       | /ver-historico/{id}   | Ver histórico de cambios        |

### 🔗 Otras Rutas Relevantes
| Método    | Ruta                   | Descripción                      | Permisos Requeridos              |
|-----------|------------------------|----------------------------------|-----------------------------------|
| GET       | /auditoria            | Registros de auditoría          | ver-auditoria                     |
| GET       | /home                 | Dashboard principal             | Autenticado                       |
| GET       | /mapa                 | Mapa interactivo                | ver-mapa                          |
