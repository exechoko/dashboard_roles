# Manual del Sistema IA Local

**Servidor:** `193.169.1.246` — Windows Server 2019 · CPU: Xeon E5-2430 v2 (AVX, sin AVX2)  
**Componentes:** Whisper (transcripción) + Ollama (LLM) + RAG

---

## Índice

1. [Estado del sistema](#1-estado-del-sistema)
2. [Servicios: levantar y detener](#2-servicios-levantar-y-detener)
3. [Cambiar modelo de Whisper](#3-cambiar-modelo-de-whisper)
4. [Cambiar modelo de Ollama](#4-cambiar-modelo-de-ollama)
5. [API de Whisper](#5-api-de-whisper)
6. [API de Ollama](#6-api-de-ollama)
7. [RAG (Retrieval-Augmented Generation)](#7-rag-retrieval-augmented-generation)
8. [Logs y troubleshooting](#8-logs-y-troubleshooting)

---

## 1. Estado del sistema

### Checklist del plan original

| Paso | Componente | Estado |
|------|-----------|--------|
| ✅ | Estructura de directorios (`C:\IA\`) | Completado |
| ✅ | Git for Windows | Instalado |
| ✅ | NSSM (gestor de servicios) | `C:\IA\nssm\nssm.exe` |
| ✅ | Visual C++ Redistributable | Instalado |
| ✅ | Python 3.11 | `C:\Python311\` |
| ✅ | Modelos Whisper descargados | `ggml-medium.bin`, `ggml-large-v3.bin` |
| ✅ | **WhisperService** (RUNNING) | Python + faster-whisper · Puerto 8080 |
| ✅ | Ollama instalado | Auto-start propio |
| ✅ | **Ollama** (RUNNING) | `llama3.2:3b` · Puerto 11434 |
| ✅ | Scripts de gestión | healthcheck, test_completo, info_sistema |
| ⚠️ | Whisper `.exe` (whisper.cpp) | **No funciona** — CPU sin AVX2/FMA3 |
| ⚠️ | Tarea programada healthcheck | Pendiente (ejecutar `setup_tarea_healthcheck.bat` como Admin) |

> **Nota sobre Whisper:** Los binarios precompilados de whisper.cpp requieren AVX2. Se reemplazaron por `faster-whisper` (Python), que funciona en modo `float32` puro y es compatible con este CPU.

### Verificación rápida

```cmd
REM Ver estado de servicios
sc query WhisperService
sc query OllamaService

REM Ver APIs
curl http://localhost:8080/health
curl http://localhost:11434/api/tags

REM Script completo
C:\IA\test_completo.bat
```

---

## 2. Servicios: levantar y detener

### WhisperService

WhisperService es un servicio Windows gestionado por NSSM. Corre `python whisper_server.py` en segundo plano.

```cmd
REM Iniciar
net start WhisperService

REM Detener
net stop WhisperService

REM Reiniciar
net stop WhisperService && net start WhisperService

REM Estado
sc query WhisperService

REM Ver configuración NSSM
C:\IA\nssm\nssm.exe edit WhisperService
```

**Tiempo de inicio:** ~10-20 segundos hasta que el modelo queda cargado en memoria.

### Ollama

Ollama gestiona su propio proceso y auto-start en Windows (no usa NSSM).

```cmd
REM Iniciar manualmente (si no está corriendo)
start /b "" "C:\Users\Administrador\AppData\Local\Programs\Ollama\ollama.exe" serve

REM Detener
taskkill /F /IM ollama.exe

REM Estado
sc query OllamaService
REM O verificar por API:
curl http://localhost:11434/api/tags
```

### Healthcheck automático

```cmd
REM Ejecutar una vez manualmente
C:\IA\healthcheck.bat

REM Instalar como tarea programada (cada 5 min) — requiere Administrador
C:\IA\setup_tarea_healthcheck.bat

REM Ver tarea
schtasks /query /tn "IA_Healthcheck"

REM Eliminar tarea
schtasks /delete /tn "IA_Healthcheck" /f
```

---

## 3. Cambiar modelo de Whisper

### Modelos disponibles

faster-whisper descarga modelos automáticamente desde HuggingFace al primer uso.

| Modelo | Tamaño | Velocidad | Calidad |
|--------|--------|-----------|---------|
| `tiny` | ~75 MB | Muy rápido | Básica |
| `base` | ~145 MB | Rápido | Básica |
| `small` | ~465 MB | Rápido | Buena |
| `medium` | ~1.5 GB | Moderado | **Muy buena** ← actual |
| `large-v2` | ~3 GB | Lento | Excelente |
| `large-v3` | ~3.1 GB | Lento | **Máxima** |

### Pasos para cambiar el modelo

**Opción A — Variable de entorno (sin editar código):**

```cmd
REM Detener servicio
net stop WhisperService

REM Configurar nuevo modelo via NSSM
C:\IA\nssm\nssm.exe set WhisperService AppEnvironmentExtra WHISPER_MODEL=large-v3

REM Iniciar servicio
net start WhisperService

REM Verificar (esperar ~30s para cargar)
curl http://localhost:8080/health
```

**Opción B — Editar directamente `whisper_server.py`:**

```python
# Línea a modificar en C:\IA\whisper\whisper_server.py
MODEL_SIZE = os.environ.get("WHISPER_MODEL", "medium")
#                                               ^^^^^^
#                              Cambiar "medium" por el modelo deseado
```

Luego reiniciar el servicio:
```cmd
net stop WhisperService && net start WhisperService
```

**Opción C — Usar modelo local ya descargado:**

Si ya tenés el modelo en cache de HuggingFace, no necesita internet. La cache está en:
```
C:\Users\Administrador\.cache\huggingface\hub\
```

Para usar una ruta local específica, editar `whisper_server.py`:
```python
# En lugar de un nombre de modelo, pasar la ruta completa:
model = WhisperModel(
    r"C:\ruta\al\modelo\ct2",  # carpeta en formato CTranslate2
    device="cpu",
    compute_type="float32"
)
```

> **Nota:** Los archivos `.bin` de `C:\IA\models\` son formato GGML (whisper.cpp). faster-whisper usa formato CTranslate2, que descarga por separado.

---

## 4. Cambiar modelo de Ollama

### Ver modelos disponibles

```cmd
REM Modelos instalados
ollama list

REM Buscar modelos en repositorio (requiere internet)
ollama search llama
```

### Descargar nuevos modelos

```cmd
REM Modelos recomendados según RAM disponible
ollama pull llama3.2:3b      REM 2 GB  — actual, equilibrado
ollama pull phi3              REM 2.2 GB — rápido, buena calidad
ollama pull mistral:7b        REM 4 GB  — muy buena calidad
ollama pull llama3.1:8b       REM 4.7 GB — excelente calidad
ollama pull qwen2.5:7b        REM 4.7 GB — muy bueno en español
ollama pull deepseek-r1:7b    REM 4.7 GB — razonamiento
```

### Eliminar un modelo

```cmd
ollama rm llama3.2:3b
```

### Cambiar el modelo por defecto en las APIs

Ollama no tiene un "modelo por defecto" — cada llamada a la API especifica el modelo.  
Si tenés una app que usa el modelo, simplemente cambiar el nombre en el body del request.

---

## 5. API de Whisper

**Base URL (red local):** `http://193.169.1.246:8080`  
**Base URL (en el propio servidor):** `http://localhost:8080`

### GET /health — Verificar estado

```bash
curl http://193.169.1.246:8080/health
```

**Respuesta:**
```json
{"model": "medium", "status": "ok"}
```
Si el modelo aún está cargando:
```json
{"model": "medium", "status": "loading"}
```

### POST /inference — Transcribir audio

```bash
curl -X POST http://193.169.1.246:8080/inference \
  -F "file=@C:\ruta\audio.wav" \
  -F "language=es"
```

**Parámetros del form:**
| Campo | Tipo | Default | Descripción |
|-------|------|---------|-------------|
| `file` | archivo | requerido | Audio (WAV, MP3, M4A, etc.) |
| `language` | string | `es` | Código de idioma (`es`, `en`, `auto`) |
| `temperature` | float | `0.0` | 0.0 = determinístico, 1.0 = creativo |

**Respuesta:**
```json
{
  "text": "Hola, esto es una prueba de transcripción.",
  "language": "es",
  "duration": 3.5
}
```

### Ejemplos prácticos

```bash
REM Transcribir en español (desde la red local)
curl -X POST http://193.169.1.246:8080/inference ^
  -F "file=@audio.wav" ^
  -F "language=es"

REM Detección automática de idioma
curl -X POST http://193.169.1.246:8080/inference ^
  -F "file=@audio.mp3" ^
  -F "language=auto"

REM Desde PowerShell
$form = @{ language = "es" }
$file = Get-Item "C:\audio.wav"
Invoke-RestMethod -Uri "http://193.169.1.246:8080/inference" `
  -Method Post -Form $form -InFile $file.FullName `
  -ContentType "multipart/form-data"
```

```python
# Python
import requests

with open("audio.wav", "rb") as f:
    response = requests.post(
        "http://193.169.1.246:8080/inference",
        files={"file": f},
        data={"language": "es"}
    )
print(response.json()["text"])
```

### Desde Laravel (PHP)

Agregar en `.env`:
```
WHISPER_URL=http://193.169.1.246:8080
```

```php
// config/services.php
'whisper' => [
    'url' => env('WHISPER_URL', 'http://193.169.1.246:8080'),
],
```

```php
// En el Controller o Service
use Illuminate\Support\Facades\Http;

public function transcribirAudio(Request $request): JsonResponse
{
    $request->validate([
        'audio' => 'required|file|mimes:mp3,wav,m4a,ogg|max:51200', // 50MB
    ]);

    $archivo = $request->file('audio');
    $url = config('services.whisper.url') . '/inference';

    $response = Http::timeout(300)
        ->attach('file', fopen($archivo->getRealPath(), 'r'), $archivo->getClientOriginalName())
        ->post($url, ['language' => 'es']);

    if ($response->failed()) {
        return response()->json(['error' => 'Error en Whisper: ' . $response->body()], 500);
    }

    return response()->json([
        'texto'    => $response->json('text'),
        'idioma'   => $response->json('language'),
        'duracion' => $response->json('duration'),
    ]);
}
```

> **Nota sobre timeouts:** La transcripción de archivos largos puede tardar varios minutos en CPU. Ajustá `Http::timeout(300)` según la duración esperada de los audios. Para archivos de más de 10 minutos, considerá correr la transcripción en un Job de Laravel Queue.

---

## 6. API de Ollama

**Base URL (red local):** `http://193.169.1.246:11434`  
**Base URL (en el propio servidor):** `http://localhost:11434`

> **Importante:** Por defecto Ollama escucha solo en `127.0.0.1`. Para que sea accesible desde la red local, configurar la variable de entorno `OLLAMA_HOST=0.0.0.0` antes de iniciar el servicio (ver sección [2. Servicios](#2-servicios-levantar-y-detener)).

### GET /api/tags — Ver modelos instalados

```bash
curl http://193.169.1.246:11434/api/tags
```

### POST /api/generate — Generar texto (sin historial)

```bash
curl http://193.169.1.246:11434/api/generate ^
  -d "{\"model\": \"llama3.2:3b\", \"prompt\": \"Explicame la fotosintesis\", \"stream\": false}"
```

**Respuesta:**
```json
{
  "model": "llama3.2:3b",
  "response": "La fotosíntesis es el proceso...",
  "done": true,
  "total_duration": 8500000000
}
```

### POST /api/chat — Chat con historial

```bash
curl http://193.169.1.246:11434/api/chat -d "{
  \"model\": \"llama3.2:3b\",
  \"stream\": false,
  \"messages\": [
    {\"role\": \"system\", \"content\": \"Sos un asistente util que responde en español.\"},
    {\"role\": \"user\", \"content\": \"¿Cuál es la capital de Francia?\"}
  ]
}"
```

**Respuesta:**
```json
{
  "model": "llama3.2:3b",
  "message": {
    "role": "assistant",
    "content": "La capital de Francia es París."
  },
  "done": true
}
```

### Ejemplos prácticos

```python
# Python — generar texto
import requests

response = requests.post("http://193.169.1.246:11434/api/generate", json={
    "model": "llama3.2:3b",
    "prompt": "Resume este texto en 3 puntos: ...",
    "stream": False
})
print(response.json()["response"])
```

```python
# Python — chat con contexto
import requests

messages = [
    {"role": "system", "content": "Sos un asistente experto en medicina."},
    {"role": "user", "content": "¿Qué es la hipertensión?"}
]

response = requests.post("http://193.169.1.246:11434/api/chat", json={
    "model": "llama3.2:3b",
    "messages": messages,
    "stream": False
})
print(response.json()["message"]["content"])
```

```python
# Python — streaming (respuesta en tiempo real)
import requests, json

with requests.post("http://193.169.1.246:11434/api/generate", json={
    "model": "llama3.2:3b",
    "prompt": "Contame un cuento corto",
    "stream": True
}, stream=True) as r:
    for line in r.iter_lines():
        if line:
            chunk = json.loads(line)
            print(chunk["response"], end="", flush=True)
```

---

## 7. RAG (Retrieval-Augmented Generation)

RAG permite que Ollama responda preguntas basadas en **tus propios documentos**, sin necesidad de reentrenar el modelo.

### Cómo funciona

```
[Tus documentos] → [Embeddings] → [Vector DB]
                                        ↓
[Pregunta del usuario] → [Búsqueda en Vector DB] → [Contexto relevante]
                                                           ↓
                                        [Ollama recibe: contexto + pregunta]
                                                           ↓
                                              [Respuesta fundamentada]
```

### Instalación

```cmd
REM Instalar dependencias RAG
C:\Python311\python.exe -m pip install chromadb langchain langchain-community sentence-transformers
```

### Script RAG básico

Guardar como `C:\IA\data\rag_server.py`:

```python
"""
RAG simple con Ollama + ChromaDB
Uso: python rag_server.py
"""
import os
import requests
import chromadb
from chromadb.utils import embedding_functions
from pathlib import Path

# Configuración
OLLAMA_URL = "http://localhost:11434"
OLLAMA_MODEL = "llama3.2:3b"
DOCS_DIR = r"C:\IA\data\documentos"
DB_DIR = r"C:\IA\data\chromadb"

# Inicializar ChromaDB con embeddings locales
ef = embedding_functions.SentenceTransformerEmbeddingFunction(
    model_name="all-MiniLM-L6-v2"  # ~90MB, se descarga automático
)
client = chromadb.PersistentClient(path=DB_DIR)
collection = client.get_or_create_collection("documentos", embedding_function=ef)


def indexar_documentos():
    """Indexa todos los .txt de DOCS_DIR en ChromaDB."""
    docs_path = Path(DOCS_DIR)
    docs_path.mkdir(parents=True, exist_ok=True)
    archivos = list(docs_path.glob("*.txt"))
    
    if not archivos:
        print(f"[INFO] No hay documentos en {DOCS_DIR}")
        print("[INFO] Agregá archivos .txt a esa carpeta y volvé a indexar.")
        return

    for archivo in archivos:
        doc_id = archivo.stem
        texto = archivo.read_text(encoding="utf-8")
        # Dividir en chunks de ~500 palabras
        palabras = texto.split()
        chunks = [" ".join(palabras[i:i+500]) for i in range(0, len(palabras), 450)]
        
        for i, chunk in enumerate(chunks):
            collection.upsert(
                ids=[f"{doc_id}_chunk_{i}"],
                documents=[chunk],
                metadatas=[{"fuente": archivo.name, "chunk": i}]
            )
        print(f"[OK] Indexado: {archivo.name} ({len(chunks)} chunks)")

    print(f"\n[OK] Total documentos indexados: {collection.count()}")


def buscar_contexto(pregunta: str, n_resultados: int = 3) -> str:
    """Busca los chunks más relevantes para la pregunta."""
    resultados = collection.query(query_texts=[pregunta], n_results=n_resultados)
    contexto_parts = []
    for doc, meta in zip(resultados["documents"][0], resultados["metadatas"][0]):
        contexto_parts.append(f"[Fuente: {meta['fuente']}]\n{doc}")
    return "\n\n---\n\n".join(contexto_parts)


def preguntar(pregunta: str) -> str:
    """Hace una pregunta usando RAG."""
    contexto = buscar_contexto(pregunta)
    
    prompt = f"""Usá SOLO la siguiente información para responder la pregunta.
Si la información no es suficiente, decilo claramente.

INFORMACIÓN:
{contexto}

PREGUNTA: {pregunta}

RESPUESTA:"""

    response = requests.post(f"{OLLAMA_URL}/api/generate", json={
        "model": OLLAMA_MODEL,
        "prompt": prompt,
        "stream": False
    })
    return response.json()["response"]


if __name__ == "__main__":
    print("=" * 50)
    print("SISTEMA RAG - Ollama + ChromaDB")
    print("=" * 50)
    
    print("\n[1] Indexando documentos...")
    indexar_documentos()
    
    print("\n[2] Modo interactivo (escribe 'salir' para terminar)")
    print("-" * 50)
    while True:
        pregunta = input("\nPregunta: ").strip()
        if pregunta.lower() in ("salir", "exit", "quit"):
            break
        if not pregunta:
            continue
        print("\nRespuesta:")
        print(preguntar(pregunta))
```

### Uso del RAG

```cmd
REM 1. Crear carpeta para tus documentos
mkdir C:\IA\data\documentos

REM 2. Poner archivos .txt en esa carpeta
copy mi_manual.txt C:\IA\data\documentos\
copy reglamento.txt C:\IA\data\documentos\

REM 3. Ejecutar el sistema RAG
C:\Python311\python.exe C:\IA\data\rag_server.py
```

### Indexar nuevos documentos sin reiniciar

```python
# Desde Python, re-indexar cuando agregues documentos nuevos:
from rag_server import indexar_documentos
indexar_documentos()
```

### RAG vía API REST (servidor HTTP)

Para exponer el RAG como API, guardar como `C:\IA\data\rag_api.py`:

```python
"""
API REST para RAG — Puerto 8081
Accesible desde la red local en http://193.169.1.246:8081
"""
import os
import uuid
import requests
from pathlib import Path
from flask import Flask, request, jsonify
from rag_server import preguntar, indexar_documentos, collection, DOCS_DIR, OLLAMA_URL, OLLAMA_MODEL

app = Flask(__name__)

FORMATOS_SOPORTADOS = {".txt", ".md", ".csv"}

# Intento opcional de soporte PDF
try:
    import fitz  # PyMuPDF: pip install pymupdf
    SOPORTE_PDF = True
except ImportError:
    SOPORTE_PDF = False


def extraer_texto(filepath: Path) -> str:
    """Extrae texto plano de un archivo según su extensión."""
    ext = filepath.suffix.lower()
    if ext in {".txt", ".md", ".csv"}:
        return filepath.read_text(encoding="utf-8", errors="ignore")
    if ext == ".pdf" and SOPORTE_PDF:
        doc = fitz.open(str(filepath))
        return "\n".join(page.get_text() for page in doc)
    raise ValueError(f"Formato no soportado: {ext}")


def resumir_con_ollama(texto: str, nombre_archivo: str) -> str:
    """Pide a Ollama un resumen estructurado del texto."""
    prompt = f"""Analizá el siguiente documento llamado "{nombre_archivo}" y generá un resumen estructurado en español.
El resumen debe incluir:
- Tema principal del documento
- Puntos clave (máximo 5)
- Conclusión o información más relevante

DOCUMENTO:
{texto[:6000]}

RESUMEN:"""

    response = requests.post(f"{OLLAMA_URL}/api/generate", json={
        "model": OLLAMA_MODEL,
        "prompt": prompt,
        "stream": False
    }, timeout=120)
    return response.json().get("response", "")


@app.route("/health")
def health():
    return jsonify({
        "status": "ok",
        "documentos": collection.count(),
        "soporte_pdf": SOPORTE_PDF,
        "formatos": list(FORMATOS_SOPORTADOS) + ([".pdf"] if SOPORTE_PDF else []),
    })


@app.route("/indexar", methods=["POST"])
def indexar():
    indexar_documentos()
    return jsonify({"status": "ok", "documentos": collection.count()})


@app.route("/preguntar", methods=["POST"])
def query():
    data = request.json
    pregunta = data.get("pregunta", "")
    if not pregunta:
        return jsonify({"error": "Campo 'pregunta' requerido"}), 400
    respuesta = preguntar(pregunta)
    return jsonify({"respuesta": respuesta})


@app.route("/cargar", methods=["POST"])
def cargar_archivo():
    """
    Recibe un archivo, lo guarda en DOCS_DIR, genera un resumen con Ollama
    y lo indexa en ChromaDB.

    Form-data:
      - file: archivo (txt, md, csv, pdf)
      - resumir: "true" | "false" (default: "true") — si generar resumen con Ollama
      - nombre: string opcional — nombre alternativo para el documento
    """
    if "file" not in request.files:
        return jsonify({"error": "Campo 'file' requerido"}), 400

    archivo = request.files["file"]
    if not archivo.filename:
        return jsonify({"error": "Nombre de archivo vacío"}), 400

    ext = Path(archivo.filename).suffix.lower()
    formatos_validos = FORMATOS_SOPORTADOS | ({".pdf"} if SOPORTE_PDF else set())
    if ext not in formatos_validos:
        return jsonify({"error": f"Formato no soportado. Aceptados: {sorted(formatos_validos)}"}), 400

    # Guardar archivo
    docs_path = Path(DOCS_DIR)
    docs_path.mkdir(parents=True, exist_ok=True)
    nombre_base = request.form.get("nombre") or Path(archivo.filename).stem
    nombre_base = "".join(c if c.isalnum() or c in "-_" else "_" for c in nombre_base)
    destino = docs_path / f"{nombre_base}{ext}"
    archivo.save(str(destino))

    # Extraer texto
    try:
        texto = extraer_texto(destino)
    except Exception as e:
        destino.unlink(missing_ok=True)
        return jsonify({"error": f"No se pudo extraer texto: {str(e)}"}), 422

    if not texto.strip():
        return jsonify({"error": "El archivo no contiene texto extraíble"}), 422

    # Resumen opcional con Ollama
    resumen = None
    if request.form.get("resumir", "true").lower() != "false":
        try:
            resumen = resumir_con_ollama(texto, archivo.filename)
            # Guardar el resumen como .txt separado para que quede en el RAG
            resumen_path = docs_path / f"{nombre_base}_resumen.txt"
            resumen_path.write_text(resumen, encoding="utf-8")
        except Exception as e:
            resumen = None
            print(f"[WARN] No se pudo generar resumen: {e}")

    # Indexar en ChromaDB (el texto completo + resumen si existe)
    indexar_documentos()

    return jsonify({
        "status": "ok",
        "archivo": destino.name,
        "caracteres": len(texto),
        "documentos_total": collection.count(),
        "resumen": resumen,
    })


if __name__ == "__main__":
    # Escuchar en 0.0.0.0 para ser accesible desde la red local
    print("RAG API corriendo en http://193.169.1.246:8081")
    indexar_documentos()
    app.run(host="0.0.0.0", port=8081)
```

#### Instalar dependencias adicionales

```cmd
REM Requerido para la API
C:\Python311\python.exe -m pip install flask

REM Opcional: soporte de PDF
C:\Python311\python.exe -m pip install pymupdf
```

#### Iniciar y usar la API

```bash
REM Iniciar API RAG (accesible desde toda la red)
C:\Python311\python.exe C:\IA\data\rag_api.py

REM Verificar estado
curl http://193.169.1.246:8081/health

REM Subir un archivo .txt y generar resumen automático
curl -X POST http://193.169.1.246:8081/cargar ^
  -F "file=@C:\ruta\reglamento.txt" ^
  -F "resumir=true"

REM Subir un PDF
curl -X POST http://193.169.1.246:8081/cargar ^
  -F "file=@C:\ruta\manual.pdf"

REM Subir sin generar resumen (solo indexar el texto)
curl -X POST http://193.169.1.246:8081/cargar ^
  -F "file=@C:\ruta\datos.txt" ^
  -F "resumir=false"

REM Consultar sobre los documentos cargados
curl -X POST http://193.169.1.246:8081/preguntar ^
  -H "Content-Type: application/json" ^
  -d "{\"pregunta\": \"¿Qué dice el reglamento sobre vacaciones?\"}"

REM Re-indexar todos los documentos de DOCS_DIR
curl -X POST http://193.169.1.246:8081/indexar
```

#### Desde Laravel (PHP)

Agregar en `.env`:
```
RAG_URL=http://193.169.1.246:8081
```

```php
// config/services.php
'rag' => [
    'url' => env('RAG_URL', 'http://193.169.1.246:8081'),
],
```

```php
// Cargar un archivo al RAG con resumen automático
use Illuminate\Support\Facades\Http;

public function cargarDocumento(Request $request): JsonResponse
{
    $request->validate([
        'documento' => 'required|file|mimes:txt,pdf,csv|max:20480', // 20MB
    ]);

    $archivo = $request->file('documento');
    $url = config('services.rag.url') . '/cargar';

    $response = Http::timeout(180)
        ->attach('file', fopen($archivo->getRealPath(), 'r'), $archivo->getClientOriginalName())
        ->post($url, ['resumir' => 'true']);

    if ($response->failed()) {
        return response()->json(['error' => 'Error al cargar en RAG: ' . $response->body()], 500);
    }

    $data = $response->json();

    return response()->json([
        'archivo'          => $data['archivo'],
        'resumen'          => $data['resumen'],
        'documentos_total' => $data['documentos_total'],
    ]);
}

// Consultar el RAG
public function consultarRAG(Request $request): JsonResponse
{
    $request->validate(['pregunta' => 'required|string|max:500']);

    $url = config('services.rag.url') . '/preguntar';

    $response = Http::timeout(60)
        ->post($url, ['pregunta' => $request->input('pregunta')]);

    if ($response->failed()) {
        return response()->json(['error' => 'Error al consultar RAG'], 500);
    }

    return response()->json(['respuesta' => $response->json('respuesta')]);
}
```

---

## 8. Logs y troubleshooting

### Ver logs

```cmd
REM Logs de WhisperService
type C:\IA\logs\whisper-stdout.log
type C:\IA\logs\whisper-stderr.log

REM Logs de Ollama (si usás NSSM)
type C:\IA\logs\ollama-stdout.log
type C:\IA\logs\ollama-stderr.log

REM En tiempo real (PowerShell)
Get-Content C:\IA\logs\whisper-stdout.log -Wait
```

### Problemas comunes

| Problema | Causa | Solución |
|----------|-------|----------|
| `WhisperService` no inicia | Puerto 8080 ocupado | `netstat -an \| findstr 8080` y matar el proceso |
| Whisper responde lento | Modelo large en CPU | Cambiar a `medium` con variable `WHISPER_MODEL` |
| Ollama no responde | Proceso caído | `start /b "" "C:\Users\Administrador\AppData\Local\Programs\Ollama\ollama.exe" serve` |
| `Out of memory` en Ollama | RAM insuficiente | Usar modelo más pequeño (`phi3` o `llama3.2:1b`) |
| faster-whisper falla | Modelo descargándose | Esperar y volver a llamar a `/health` |

### Comandos de diagnóstico

```cmd
REM Puertos en uso
netstat -an | findstr "8080 11434 8081"

REM Verificar acceso desde la red (ejecutar desde otra máquina)
curl http://193.169.1.246:8080/health
curl http://193.169.1.246:11434/api/tags
curl http://193.169.1.246:8081/health

REM Memoria RAM disponible
wmic OS get FreePhysicalMemory,TotalVisibleMemorySize

REM Procesos IA corriendo
tasklist | findstr "python ollama"

REM Información completa del sistema
C:\IA\info_sistema.bat
```

---

## Resumen de puertos

**Servidor IA:** `193.169.1.246` (Windows Server 2019 — red local)

| Puerto | Servicio | URL de red | Descripción |
|--------|---------|------------|-------------|
| `8080` | WhisperService | `http://193.169.1.246:8080` | Transcripción de audio |
| `11434` | Ollama | `http://193.169.1.246:11434` | LLM (generación de texto) |
| `8081` | RAG API | `http://193.169.1.246:8081` | Carga de archivos, resumen y consulta por documentos |

### Variables de entorno en Laravel (`.env`)

```
WHISPER_URL=http://193.169.1.246:8080
RAG_URL=http://193.169.1.246:8081
OLLAMA_URL=http://193.169.1.246:11434
```

---

*Generado: 2026-04-08 · Sistema: C:\IA · Servidor: 193.169.1.246*
