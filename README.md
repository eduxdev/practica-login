# Laboratorio: Base de Datos Vulnerable y Segura con Simulación de Ataques Web

## BANCO PATITO S.A. DE C.V.

---

## 1. Objetivo del Laboratorio

Crear un sistema web bancario con **dos fases**:
- **Fase 1 (Vulnerable):** Login con consultas SQL por concatenación directa, contraseñas en texto plano. Permite demostrar SQL Injection y captura de credenciales por HTTP.
- **Fase 2 (Seguro):** Login con Prepared Statements, contraseñas con bcrypt (`password_hash` / `password_verify`) y límite de intentos fallidos.

> **ADVERTENCIA:** Este proyecto es SOLO para entorno académico controlado con XAMPP. No usar en producción.

---

## 2. Estructura de Carpetas

```
control/
├── index.php                 # Página principal con enlaces a ambos logins
├── login_vulnerable.php      # Fase 1 - Login vulnerable a SQL Injection
├── login_seguro.php          # Fase 2 - Login seguro con Prepared Statements
├── dashboard.php             # Panel principal del banco
├── generar_hashes.php        # Herramienta para convertir contraseñas a bcrypt
├── logout.php                # Cierre de sesión
├── cerrar_sesion.php         # Cierre de sesión (alias)
├── config/
│   └── conexion.php          # Conexión a la base de datos MySQL
├── assets/
│   └── css/
│       └── estilos.css       # Estilos CSS del sistema bancario
├── ataques/
│   ├── brute_force.py        # Script Python de fuerza bruta
│   └── passwords.txt         # Diccionario de contraseñas comunes
└── README.md                 # Este archivo
```

---

## 3. Cómo Importar la Base de Datos

1. Abrir **phpMyAdmin** en `http://localhost/phpmyadmin`
2. Crear la base de datos `validar` (si no existe)
3. Importar el archivo SQL que contiene las tablas:
   - `usuarios` (id_usuario, usuario, password, correo, rol, estado)
   - `clientes` (id_cliente, nombre, apellido_paterno, apellido_materno, curp, telefono, correo, direccion, fecha_registro)
   - `cuentas` (id_cuenta, id_cliente, numero_cuenta, tipo_cuenta, saldo, fecha_apertura, estado)
   - `tarjetas` (id_tarjeta, id_cuenta, numero_tarjeta, tipo_tarjeta, fecha_vencimiento, cvv, estado)
   - `prestamos` (id_prestamo, id_cliente, monto, tasa_interes, plazo_meses, estado, fecha_solicitud)
   - `transacciones` (id_transaccion, id_cuenta, tipo_transaccion, monto, fecha_transaccion, descripcion)

---

## 4. Cómo Ejecutar en XAMPP

1. Iniciar **Apache** y **MySQL** desde el panel de control de XAMPP.
2. Colocar la carpeta del proyecto en `C:\xampp\htdocs\control\`
3. Abrir el navegador y visitar: `http://localhost/control/`
4. Aparecerá la página principal con dos opciones de login.

---

## 5. Usuario de Prueba

### Login Vulnerable (Fase 1)
- **Usuario:** `admin`
- **Contraseña:** `789`

### Login Seguro (Fase 2)
Para usar el login seguro, primero debes generar los hashes bcrypt:
1. Visita `http://localhost/control/generar_hashes.php`
2. Haz clic en "Hashear Todas las Contraseñas en Texto Plano"
3. Ahora puedes usar el mismo usuario y contraseña en el login seguro.

---

## 6. Diferencia entre Login Vulnerable y Login Seguro

| Característica | Login Vulnerable (Fase 1) | Login Seguro (Fase 2) |
|---|---|---|
| Consulta SQL | Concatenación directa | Prepared Statements |
| Contraseñas | Texto plano | Hashes bcrypt |
| SQL Injection | **Vulnerable** | **Protegido** |
| Límite de intentos | No | Sí (5 intentos, bloqueo 60s) |
| Verificación password | Comparación directa | `password_verify()` |

### Ejemplo de SQL Injection (Fase 1)
En el campo de **usuario**, ingresar cualquiera de estas opciones (la contraseña puede ser cualquier cosa):

**Opción 1 — Acceso como el primer usuario (admin):**
```
' OR 1=1#
```

**Opción 2 — Acceso como un usuario específico (bypass de contraseña):**
```
admin' #
```
Esto comenta la parte de `AND password = '...'`, entrando directamente como el usuario `admin` sin saber su contraseña. Funciona con cualquier nombre de usuario existente.

**Opción 3** (usando `-- ` con espacio al final):
```
' OR '1'='1' -- 
```

> **Nota:** En MySQL/MariaDB el comentario `--` requiere un espacio después. Si usas `--` sin espacio dará error de sintaxis. La opción más sencilla es usar `#`.

---

## 7. Escenario de Ataque Completo: Robo de Fondos

Este es el escenario paso a paso que demuestra cómo un atacante puede explotar las vulnerabilidades del login para robar dinero de otro cliente.

### Paso 1 — Acceso como administrador (SQL Injection)
El atacante entra al login vulnerable y usa inyección SQL para entrar como admin:
- **Usuario:** `' OR 1=1#`
- **Contraseña:** cualquier cosa

Esto lo lleva al dashboard con privilegios de **administrador**.

### Paso 2 — Reconocimiento: ver todos los usuarios
Desde el panel de admin, el atacante navega a **Usuarios** y observa todos los usuarios del sistema: sus nombres de usuario, correos y roles. Identifica qué usuarios tienen rol **cliente** (potenciales víctimas con cuentas bancarias y saldo).

### Paso 3 — El atacante se crea su propia cuenta
Desde **Usuarios → Crear Usuario**, el atacante crea su propia cuenta:
- **Usuario:** `hacker123`
- **Correo:** `hacker@mail.com`
- **Contraseña:** `mipass`
- **Rol:** `cliente`
- **Estado:** `activo`

### Paso 4 — Se registra como cliente del banco
En la sección **Clientes**, selecciona su usuario recién creado (`hacker123`) del dropdown y llena sus datos personales (nombre, apellidos, etc.).

### Paso 5 — Se crea una cuenta bancaria
En la sección **Cuentas → Crear Nueva Cuenta**, selecciona su perfil de cliente y crea una cuenta de ahorro con saldo $0. Anota su **número de cuenta** (ej: `1012345678`).

### Paso 6 — Cierra sesión
El atacante cierra sesión para preparar la suplantación de identidad.

### Paso 7 — Suplanta a la víctima (SQL Injection dirigido)
Vuelve al login vulnerable y esta vez entra como un usuario específico (la víctima que identificó en el paso 2):
- **Usuario:** `carlos' #`
- **Contraseña:** cualquier cosa

La consulta SQL resultante:
```sql
SELECT * FROM usuarios WHERE usuario = 'carlos' #' AND password = 'x'
```
El `#` comenta toda la verificación de contraseña. El atacante ahora está logueado como **carlos** con todos sus permisos y cuentas bancarias.

### Paso 8 — Transferencia de fondos
Navega a **Transferir**, donde ve las cuentas de la víctima con su saldo. Realiza una transferencia:
- **Cuenta origen:** la cuenta de la víctima (ya cargada porque está logueado como ella)
- **Cuenta destino:** el número de cuenta del atacante (el que anotó en el paso 5)
- **Monto:** la cantidad que desea robar

El dinero se transfiere exitosamente de la cuenta de la víctima a la del atacante.

### Resumen del ataque
| Paso | Acción | Vulnerabilidad Explotada |
|------|--------|--------------------------|
| 1 | Entrar como admin | SQL Injection (`' OR 1=1#`) |
| 2 | Ver todos los usuarios | Acceso admin (escalación de privilegios) |
| 3-5 | Crear usuario, cliente y cuenta propios | Abuso de privilegios de admin |
| 7 | Entrar como la víctima | SQL Injection dirigido (`usuario' #`) |
| 8 | Transferir dinero a su cuenta | Suplantación de identidad |

---

## 8. Ataque de Fuerza Bruta con Python (Windows)

El login vulnerable no tiene límite de intentos, lo que permite probar miles de contraseñas automáticamente.

### Requisitos

1. Instalar **Python** desde [python.org](https://www.python.org/downloads/) (marcar "Add to PATH" durante la instalación)
2. Instalar la librería `requests`:
```
pip install requests
```

### Archivos incluidos

```
ataques/
├── brute_force.py    # Script de fuerza bruta
└── passwords.txt     # Diccionario con 100 contraseñas comunes
```

### Cómo ejecutar el ataque

1. Asegúrate de que **Apache** y **MySQL** estén corriendo en XAMPP.
2. Abre una terminal (CMD o PowerShell) y navega a la carpeta del proyecto:
```
cd C:\xampp\htdocs\control\ataques
```
3. Ejecuta el script:
```
python brute_force.py
```
4. Ingresa el usuario a atacar (por defecto: `admin`) y confirma con `s`.

### Uso con argumentos (opcional)

```
python brute_force.py admin passwords.txt
```

### Resultado esperado

El script probará cada contraseña del diccionario contra el login vulnerable. Cuando encuentre la correcta (`789` para el usuario `admin`), mostrará:

```
[67/100] ✅ CONTRASEÑA ENCONTRADA: 789

============================================================
  🔓 Usuario:     admin
  🔑 Contraseña:  789
  ⏱️  Tiempo:      1.25 segundos
  📊 Intentos:    67 de 100
============================================================
```

### Evidencias para el laboratorio

1. Captura del script ejecutándose con intentos fallidos.
2. Captura del momento en que encuentra la contraseña.
3. Comparar contra el login seguro (Fase 2) que bloquea después de 5 intentos.

---

## 9. Cómo el Login Seguro Previene Este Ataque

| Vulnerabilidad | Login Vulnerable | Login Seguro |
|---|---|---|
| SQL Injection | `' OR 1=1#` funciona | Prepared Statements bloquean la inyección |
| Bypass de contraseña | `usuario' #` omite password | `password_verify()` siempre se ejecuta |
| Fuerza bruta | Sin límite | 5 intentos, bloqueo 60 segundos |
| Contraseñas en BD | Texto plano (legibles) | Hashes bcrypt (ilegibles) |
| Captura en red | HTTP envía en texto plano | Igual (requiere HTTPS adicional) |

---

## 10. Evidencias Recomendadas para el Laboratorio

1. **Login vulnerable:** Captura del formulario de login Fase 1.
2. **Bypass con SQL Injection:** Captura entrando con `' OR 1=1#` como admin.
3. **Reconocimiento:** Captura de la lista de usuarios desde el panel de admin.
4. **Creación de cuenta del atacante:** Capturas de crear usuario, cliente y cuenta bancaria.
5. **Suplantación de identidad:** Captura entrando como otro usuario con `usuario' #`.
6. **Robo de fondos:** Captura de la transferencia desde la cuenta de la víctima.
7. **Wireshark:** Captura de POST con credenciales viajando en texto plano por HTTP.
8. **Login seguro:** Captura mostrando que el SQL Injection **no funciona** en Fase 2.
9. **Código seguro:** Captura del código de `login_seguro.php` con Prepared Statements.
10. **Hashes bcrypt:** Captura de `generar_hashes.php` mostrando los hashes generados.

---

## Nota Importante

Todo esto es para una **práctica escolar** en un entorno local con XAMPP. **No usar en producción.**
