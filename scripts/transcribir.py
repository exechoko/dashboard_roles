# scripts/transcribir.py

import whisper
import sys
import json
import os
import asyncio

# Fuerza el selector de eventos para Windows
if sys.platform == 'win32':
    asyncio.set_event_loop_policy(asyncio.WindowsSelectorEventLoopPolicy())  # <-- Añade esto

# Agregar ffmpeg manualmente al PATH
os.environ["PATH"] += os.pathsep + "C:\\ffmpeg\\ffmpeg-7.1.1-essentials_build\\bin"  # Ajustá si tenés otra ruta

if len(sys.argv) < 2:
    print(json.dumps({"success": False, "text": "No se proporcionó la ruta del archivo."}))
    sys.exit()

ruta_audio = sys.argv[1]

try:
    modelo = whisper.load_model("base")
    resultado = modelo.transcribe(ruta_audio)
    print(json.dumps({"success": True, "text": resultado["text"]}, ensure_ascii=False))
except Exception as e:
    print(json.dumps({"success": False, "text": str(e)}))
