"""
==============================================================================
KYNAP FITNESS PLATFORM - SECURE GROQ AI ROUTINE SERVICE (LLAMA 3.3)
==============================================================================
Módulo aislado para la generación de rutinas personalizadas con Groq AI.
REGLA DE SEGURIDAD ARQUITECTÓNICA:
- Este módulo NO tiene acceso, dependencias ni conexiones a la base de datos.
- Recibe únicamente el contexto biométrico sanitizado por stdin (JSON).
- Lee la clave GROQ_API_KEY exclusivamente desde las variables de entorno del servidor (.env).
- Realiza control de excepciones, timeouts y validación estricta de esquemas.
==============================================================================
"""

import sys
import os
import json
import re
from typing import Dict, Any, List

def load_env_file(filepath: str) -> Dict[str, str]:
    """Carga variables desde archivo .env sin dependencias externas."""
    env_vars = {}
    if os.path.exists(filepath):
        try:
            with open(filepath, 'r', encoding='utf-8') as f:
                for line in f:
                    line = line.strip()
                    if not line or line.startswith('#') or '=' not in line:
                        continue
                    key, val = line.split('=', 1)
                    env_vars[key.strip()] = val.strip().strip('"\'')
        except Exception as e:
            sys.stderr.write(f"[WARN] Error reading .env: {e}\n")
    return env_vars

def get_config_var(key: str, default: str = "") -> str:
    """Obtiene variable de entorno de os.environ o del archivo .env."""
    if os.environ.get(key):
        return os.environ[key]
    
    # Buscar .env en la raíz del proyecto
    script_dir = os.path.dirname(os.path.abspath(__file__))
    root_dir = os.path.dirname(script_dir)
    env_path = os.path.join(root_dir, '.env')
    env_data = load_env_file(env_path)
    return env_data.get(key, default)

def validate_routine_schema(data: Dict[str, Any]) -> bool:
    """Valida estrictamente que el objeto de rutina cumpla con el esquema requerido."""
    if not isinstance(data, dict):
        return False
    
    if not data.get("name") or not isinstance(data["name"], str):
        return False
        
    days = data.get("days")
    if not isinstance(days, list) or len(days) < 5:
        return False
        
    valid_day_ids = {"lunes", "martes", "miercoles", "jueves", "viernes", "sabado", "domingo"}
    for day in days:
        if not isinstance(day, dict):
            return False
        if "id" not in day or day["id"] not in valid_day_ids:
            return False
        if "dayName" not in day or "focus" not in day or "isRest" not in day:
            return False
        if not isinstance(day.get("exercises"), list):
            return False
            
    return True

def generate_expert_fallback_routine(context: Dict[str, Any], reason: str = "") -> Dict[str, Any]:
    """
    Generador de resiliencia: Diseña una rutina de alta calidad basada en fisiología deportiva
    cuando la API externa no está disponible o la clave aún no está configurada.
    """
    objetivo = str(context.get("objetivo", "ganar_musculo")).lower()
    peso = float(context.get("peso", 75.0))
    dias = int(context.get("dias_semana", 4))
    
    if objetivo == "ganar_musculo":
        name = "Kynap Hypertrophy Precision (4 Días)"
        desc = "Periodización ondulante enfocada en tensión mecánica y sobrecarga progresiva para máxima síntesis proteica."
        lunes_focus = "Pierna & Glúteos (Énfasis Cuádriceps y Cadena Posterior)"
        lunes_ex = [
            {"name": "Sentadillas", "muscle": "cuadriceps", "sets": 4, "reps": "8-10", "rpe": 8.0, "rest_seconds": 120, "notes": "Profundidad paralela y tempo controlado 3-0-1."},
            {"name": "Puente de Glúteos", "muscle": "gluteos", "sets": 4, "reps": "10-12", "rpe": 8.5, "rest_seconds": 90, "notes": "Pausa isométrica de 2 segundos en la contracción máxima."},
            {"name": "Estocada Hacia Atrás", "muscle": "cuadriceps", "sets": 3, "reps": "10-12", "rpe": 8.0, "rest_seconds": 75, "notes": "Estabilidad pélvica y rodilla alineada con la punta del pie."}
        ]
        martes_focus = "Torso Superior (Empuje & Tracción Fundamental)"
        martes_ex = [
            {"name": "Flexiones con Inclinación", "muscle": "pecho", "sets": 4, "reps": "10-12", "rpe": 8.0, "rest_seconds": 90, "notes": "Rango completo de movimiento con retracción escapular."},
            {"name": "Fondos de Tríceps en Silla", "muscle": "triceps", "sets": 3, "reps": "12-15", "rpe": 8.5, "rest_seconds": 75, "notes": "Codos cerrados hacia atrás sin hiperextensión de hombro."},
            {"name": "Plancha Tradicional", "muscle": "abdomen", "sets": 3, "reps": "45-60s", "rpe": 8.0, "rest_seconds": 60, "notes": "Activación glútea y abdominal simultánea."}
        ]
        jueves_focus = "Potencia y Acondicionamiento de Cadena Posterior"
        jueves_ex = [
            {"name": "Elevación de Piernas para Glúteos", "muscle": "gluteos", "sets": 3, "reps": "15", "rpe": 8.0, "rest_seconds": 60, "notes": "Evitar arquear la zona lumbar."},
            {"name": "Step en Silla", "muscle": "cuadriceps", "sets": 3, "reps": "12", "rpe": 8.0, "rest_seconds": 75, "notes": "Impulso proveniente de la pierna de apoyo elevada."},
            {"name": "Crunch Inverso", "muscle": "abdomen", "sets": 3, "reps": "15", "rpe": 8.0, "rest_seconds": 60, "notes": "Despegue pélvico suave sin balanceo inercial."}
        ]
        viernes_focus = "Full Body Funcional & Core Dinámico"
        viernes_ex = [
            {"name": "Burpees", "muscle": "cardio", "sets": 4, "reps": "10-12", "rpe": 8.5, "rest_seconds": 90, "notes": "Transición fluida manteniendo alineación lumbo-pélvica."},
            {"name": "Crunch de Bicicleta", "muscle": "abdomen", "sets": 3, "reps": "20", "rpe": 8.0, "rest_seconds": 60, "notes": "Rotación torácica controlada sin jalar la nuca."},
            {"name": "Mountain Climbers", "muscle": "abdomen", "sets": 3, "reps": "30s", "rpe": 8.5, "rest_seconds": 60, "notes": "Ritmo constante manteniendo hombros sobre muñecas."}
        ]
    elif objetivo == "perder_grasa":
        name = "Kynap Metabolic Shred (4 Días)"
        desc = "Entrenamiento metabólico de alta densidad para preservar masa muscular y maximizar gasto calórico."
        lunes_focus = "Full Body Metabólico & HIIT"
        lunes_ex = [
            {"name": "Burpees", "muscle": "cardio", "sets": 4, "reps": "12", "rpe": 8.5, "rest_seconds": 60, "notes": "Mantener cadencia continua."},
            {"name": "Sentadillas", "muscle": "cuadriceps", "sets": 4, "reps": "15", "rpe": 8.0, "rest_seconds": 60, "notes": "Rango completo de recorrido."},
            {"name": "Elevación de Rodillas", "muscle": "cardio", "sets": 3, "reps": "30s", "rpe": 8.5, "rest_seconds": 45, "notes": "Amortiguación suave con metatarsos."}
        ]
        martes_focus = "Fuerza de Torso & Core"
        martes_ex = [
            {"name": "Flexiones con Inclinación", "muscle": "pecho", "sets": 3, "reps": "12-15", "rpe": 8.0, "rest_seconds": 60, "notes": "Empuje explosivo y descenso controlado."},
            {"name": "Crunch Abdominal", "muscle": "abdomen", "sets": 3, "reps": "20", "rpe": 8.0, "rest_seconds": 45, "notes": "Flexión torácica controlada."},
            {"name": "Twist Ruso", "muscle": "abdomen", "sets": 3, "reps": "20", "rpe": 8.0, "rest_seconds": 45, "notes": "Rotación desde el núcleo abdominal."}
        ]
        jueves_focus = "Fuerza de Piernas & Resistencia"
        jueves_ex = [
            {"name": "Zancada Frontal", "muscle": "cuadriceps", "sets": 3, "reps": "12", "rpe": 8.0, "rest_seconds": 60, "notes": "Zancada amplia para proteger la rótula."},
            {"name": "Puente de Glúteos", "muscle": "gluteos", "sets": 3, "reps": "15", "rpe": 8.0, "rest_seconds": 45, "notes": "Contracción sostenida de glúteo en el punto alto."},
            {"name": "Mountain Climbers", "muscle": "abdomen", "sets": 3, "reps": "30s", "rpe": 8.5, "rest_seconds": 45, "notes": "Máxima cadencia cardiovascular."}
        ]
        viernes_focus = "Circuito Acondicionamiento Total"
        viernes_ex = [
            {"name": "Step en Silla", "muscle": "cuadriceps", "sets": 3, "reps": "15", "rpe": 8.0, "rest_seconds": 45, "notes": "Alternar pierna guía en cada serie."},
            {"name": "Fondos de Tríceps en Silla", "muscle": "triceps", "sets": 3, "reps": "12", "rpe": 8.0, "rest_seconds": 60, "notes": "Mantener la espalda cercana al borde de la silla."},
            {"name": "Plancha Tradicional", "muscle": "abdomen", "sets": 3, "reps": "45s", "rpe": 8.5, "rest_seconds": 45, "notes": "Tensión isométrica total."}
        ]
    else:
        name = "Kynap Functional Fitness & Salud (4 Días)"
        desc = "Acondicionamiento balanceado enfocado en movilidad articular, resistencia muscular y longevidad física."
        lunes_focus = "Fuerza Funcional de Tren Inferior"
        lunes_ex = [
            {"name": "Sentadillas", "muscle": "cuadriceps", "sets": 3, "reps": "12", "rpe": 7.5, "rest_seconds": 90, "notes": "Control postural equilibrado."},
            {"name": "Puente de Glúteos", "muscle": "gluteos", "sets": 3, "reps": "12", "rpe": 7.5, "rest_seconds": 75, "notes": "Activación consciente de la musculatura posterior."},
            {"name": "Elevación de Rodillas", "muscle": "cardio", "sets": 3, "reps": "30s", "rpe": 7.5, "rest_seconds": 60, "notes": "Ritmo moderado y respiración fluida."}
        ]
        martes_focus = "Estabilidad Escapular & Core"
        martes_ex = [
            {"name": "Flexiones con Inclinación", "muscle": "pecho", "sets": 3, "reps": "10", "rpe": 7.5, "rest_seconds": 90, "notes": "Tronco en línea recta."},
            {"name": "Crunch de Bicicleta", "muscle": "abdomen", "sets": 3, "reps": "15", "rpe": 7.5, "rest_seconds": 60, "notes": "Velocidad constante y controlada."},
            {"name": "Plancha Tradicional", "muscle": "abdomen", "sets": 3, "reps": "30-45s", "rpe": 7.5, "rest_seconds": 60, "notes": "Respiración profunda diafragmática."}
        ]
        jueves_focus = "Resistencia Dinámica de Piernas"
        jueves_ex = [
            {"name": "Estocada Hacia Atrás", "muscle": "cuadriceps", "sets": 3, "reps": "10", "rpe": 7.5, "rest_seconds": 75, "notes": "Paso firme y seguro."},
            {"name": "Step en Silla", "muscle": "cuadriceps", "sets": 3, "reps": "10", "rpe": 7.5, "rest_seconds": 75, "notes": "Control en el descenso."},
            {"name": "Twist Ruso", "muscle": "abdomen", "sets": 3, "reps": "16", "rpe": 7.5, "rest_seconds": 60, "notes": "Mirada al frente sin tensar cuello."}
        ]
        viernes_focus = "Acondicionamiento y Recuperación Activa"
        viernes_ex = [
            {"name": "Burpees", "muscle": "cardio", "sets": 3, "reps": "8-10", "rpe": 8.0, "rest_seconds": 90, "notes": "Intensidad moderada adaptada."},
            {"name": "Crunch Inverso", "muscle": "abdomen", "sets": 3, "reps": "12", "rpe": 7.5, "rest_seconds": 60, "notes": "Movimiento lento y consciente."},
            {"name": "Mountain Climbers", "muscle": "abdomen", "sets": 3, "reps": "25s", "rpe": 7.5, "rest_seconds": 60, "notes": "Activación continua del core."}
        ]

    routine = {
        "name": name,
        "description": desc,
        "target_days": dias,
        "ai_engine": "Kynap Sports Science (Resilience Fallback)" if reason else "Kynap Core Engine",
        "fallback_reason": reason,
        "days": [
            {"id": "lunes", "dayName": "Lunes", "focus": lunes_focus, "isRest": False, "exercises": lunes_ex},
            {"id": "martes", "dayName": "Martes", "focus": martes_focus, "isRest": False, "exercises": martes_ex},
            {"id": "miercoles", "dayName": "Miércoles", "focus": "Descanso Activo & Regeneración Celular", "isRest": True, "exercises": []},
            {"id": "jueves", "dayName": "Jueves", "focus": jueves_focus, "isRest": False, "exercises": jueves_ex},
            {"id": "viernes", "dayName": "Viernes", "focus": viernes_focus, "isRest": False, "exercises": viernes_ex},
            {"id": "sabado", "dayName": "Sábado", "focus": "Recuperación Muscular & Hidratación", "isRest": True, "exercises": []},
            {"id": "domingo", "dayName": "Domingo", "focus": "Descanso Total & Preparación Semanal", "isRest": True, "exercises": []}
        ]
    }
    return routine

def generate_routine_with_groq(context: Dict[str, Any]) -> Dict[str, Any]:
    """
    Invoca la API de Groq con el modelo Llama 3.3 para generar una rutina experta.
    Maneja timeouts, excepciones y validación de esquema.
    """
    api_key = get_config_var("GROQ_API_KEY", "")
    model = get_config_var("GROQ_MODEL", "llama-3.3-70b-versatile")
    timeout = float(get_config_var("GROQ_TIMEOUT_SECONDS", "30"))

    # Si la clave es un placeholder o no está configurada, usar el generador experto de resiliencia
    if not api_key or api_key.startswith("gsk_demo") or api_key == "your_groq_api_key_here":
        return generate_expert_fallback_routine(context, "Clave GROQ_API_KEY en modo plantilla. Se generó rutina científica verificada.")

    try:
        from groq import Groq
    except ImportError:
        return generate_expert_fallback_routine(context, "Librería groq no importable en el entorno actual.")

    # Construir prompt altamente estructurado
    system_prompt = (
        "Eres el Director de Fisiología del Ejercicio y Alto Rendimiento de Kynap AI, certificado NSCA-CSCS. "
        "Tu misión es diseñar una rutina de entrenamiento semanal basada estrictamente en la evidencia científica "
        "(sobrecarga progresiva, volumen landmark, fatiga y biomecánica). "
        "DEBES RESPONDER EXCLUSIVAMENTE CON UN OBJETO JSON VÁLIDO conforme al siguiente esquema:\n"
        "{\n"
        '  "name": "Nombre motivador y profesional de la rutina",\n'
        '  "description": "Breve fundamentación fisiológica de la rutina",\n'
        '  "target_days": 4,\n'
        '  "days": [\n'
        '    {\n'
        '      "id": "lunes",\n'
        '      "dayName": "Lunes",\n'
        '      "focus": "Foco muscular o descanso",\n'
        '      "isRest": false,\n'
        '      "exercises": [\n'
        '        {\n'
        '          "name": "Nombre del ejercicio",\n'
        '          "muscle": "cuadriceps|gluteos|pecho|espalda|hombros|biceps|triceps|abdomen|cardio",\n'
        '          "sets": 4,\n'
        '          "reps": "8-10",\n'
        '          "rpe": 8.0,\n'
        '          "rest_seconds": 90,\n'
        '          "notes": "Consejo biomecánico conciso"\n'
        '        }\n'
        '      ]\n'
        '    }\n'
        '  ]\n'
        "}\n"
        "Reglas obligatorias:\n"
        "1. Debes incluir exactamente los 7 días de la semana: lunes, martes, miercoles, jueves, viernes, sabado, domingo.\n"
        "2. Los días de descanso deben tener isRest: true y exercises: [].\n"
        "3. Para los días de entrenamiento, incluye entre 3 y 5 ejercicios por día.\n"
        "4. No añadas texto explicativo fuera del JSON."
    )

    user_prompt = (
        f"Diseña una rutina semanal óptima para el siguiente atleta de Kynap:\n"
        f"- Nombre: {context.get('nombre', 'Atleta')}\n"
        f"- Edad: {context.get('edad', 25)} años\n"
        f"- Peso: {context.get('peso', 75)} kg\n"
        f"- Altura: {context.get('altura', 175)} cm\n"
        f"- Sexo: {context.get('sexo', 'masculino')}\n"
        f"- Objetivo principal: {context.get('objetivo', 'ganar_musculo')}\n"
        f"- Nivel de actividad: {context.get('nivel_actividad', 'moderado')}\n"
        f"- Días de entrenamiento deseados: {context.get('dias_semana', 4)} días a la semana\n"
        f"- Condiciones o limitaciones: {context.get('condiciones', 'ninguna')}\n"
        f"- Alergias/Preferencias: {context.get('preferencia', 'gimnasio')}\n"
    )

    try:
        client = Groq(api_key=api_key)
        response = client.chat.completions.create(
            model=model,
            messages=[
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": user_prompt}
            ],
            response_format={"type": "json_object"},
            temperature=0.6,
            timeout=timeout
        )

        content = response.choices[0].message.content.strip()
        
        # Limpieza defensiva de bloques markdown ```json ... ```
        clean_content = re.sub(r'^```(?:json)?\s*', '', content)
        clean_content = re.sub(r'\s*```$', '', clean_content)
        
        parsed = json.loads(clean_content)

        if validate_routine_schema(parsed):
            parsed["ai_engine"] = f"Groq Llama 3.3 ({model})"
            return parsed
        else:
            return generate_expert_fallback_routine(context, "Respuesta de Llama no cumplió con el esquema validado.")

    except json.JSONDecodeError as je:
        sys.stderr.write(f"[WARN] Error decodificando JSON de Groq: {je}\n")
        return generate_expert_fallback_routine(context, "Error en formato JSON de la respuesta de IA.")
    except Exception as e:
        sys.stderr.write(f"[WARN] Error comunicando con la API de Groq: {e}\n")
        return generate_expert_fallback_routine(context, f"Fallo de conexión o timeout con la API externa ({type(e).__name__}).")

def main():
    """Punto de entrada CLI / Subproceso para el backend."""
    # Configurar UTF-8 forzado en stdin y stdout para compatibilidad cross-platform en Windows
    if sys.platform.startswith('win'):
        try:
            sys.stdin.reconfigure(encoding='utf-8')
            sys.stdout.reconfigure(encoding='utf-8')
        except Exception:
            pass

    try:
        raw_input = sys.stdin.read().strip()
        if not raw_input:
            context = {}
        else:
            context = json.loads(raw_input)
    except Exception as e:
        sys.stderr.write(f"[ERROR] Entrada JSON inválida en stdin: {e}\n")
        context = {}

    result = generate_routine_with_groq(context)
    json_bytes = json.dumps(result, ensure_ascii=False).encode('utf-8')
    sys.stdout.buffer.write(json_bytes)
    sys.stdout.buffer.flush()

if __name__ == "__main__":
    main()
