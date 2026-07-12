<div align="center">

## 🎓 MAIPI
### **Monitoring and Academic Intervention Platform for Student Retention**

Sistema Inteligente de Monitoreo para la Permanencia Estudiantil en la  
**Universidad Nacional Agraria La Molina (UNALM)**

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-2-42B883?style=for-the-badge&logo=vuedotjs&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-4-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![Chart.js](https://img.shields.io/badge/Chart.js-F5788D?style=for-the-badge&logo=chartdotjs&logoColor=white)
![SweetAlert2](https://img.shields.io/badge/SweetAlert2-FF4B4B?style=for-the-badge)

![GitHub last commit](https://img.shields.io/github/last-commit/joseluis02678/MAIPI)
![GitHub repo size](https://img.shields.io/github/repo-size/joseluis02678/MAIPI)
![GitHub stars](https://img.shields.io/github/stars/joseluis02678/MAIPI?style=social)

</div>

---

## 📖 Overview

**MAIPI (Monitoring and Academic Intervention Platform)** es una plataforma web desarrollada para la **Universidad Nacional Agraria La Molina (UNALM)** que permite identificar tempranamente estudiantes con riesgo de deserción mediante el análisis de indicadores académicos, socioeconómicos y de asistencia.

El sistema proporciona herramientas de monitoreo, visualización y gestión de alertas para facilitar la toma de decisiones por parte de las autoridades universitarias.

---

## 🚀 Objetivos

✔ Detectar estudiantes en riesgo.

✔ Automatizar el seguimiento académico.

✔ Centralizar la información institucional.

✔ Apoyar la toma de decisiones mediante dashboards.

✔ Reducir la deserción estudiantil.

---

## 📂 Estructura del Proyecto

```text
MAIPI/
│
├── README.md
├── index.php
├── .gitignore
│
├── config/
│   ├── conexion.php
│   └── db.php
│
├── pages/
│   ├── admin.php
│   └── admin2.php
│
├── data/
│   ├── alumnos.csv
│   ├── cursos.csv
│   ├── facultades.csv
│   ├── escuelas.csv
│   ├── matriculas.csv
│   └── padron_2025.xlsx
│
├── docs/
│   ├── bdtrabajo2024.pdf
│   └── estructura del trabajo.docx
│
├── images/
│   ├── dashboard.png
│   ├── arquitectura.png
│   └── logo_unalm.png
│
├── sql/
│   └── maipi.sql
│
├── css/
│
├── js/
│
└── assets/
```

---

## ✨ Funcionalidades

### 📊 Dashboard Ejecutivo

- Indicadores generales
- Distribución del riesgo
- Número de estudiantes
- Estado académico
- Estadísticas en tiempo real

---

### 🎯 Sistema Inteligente de Riesgo

El algoritmo evalúa múltiples variables:

- Promedio ponderado
- Asistencia
- Número de cursos desaprobados
- Repetición de asignaturas
- Historial académico
- Condición socioeconómica

Clasificando automáticamente al estudiante como:

| Nivel | Color |
|--------|--------|
| 🔴 Crítico | Alta prioridad |
| 🟠 Alto | Seguimiento inmediato |
| 🟡 Medio | Monitoreo |
| 🟢 Bajo | Sin riesgo |

---

### 📈 Visualización

- Doughnut Charts
- Bar Charts
- Indicadores KPI
- Estadísticas dinámicas
- Paneles interactivos

---

## 🏗 Arquitectura

```text
MAIPI/
│
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│
├── config/
│   └── db.php
│
├── controllers/
│
├── models/
│
├── views/
│
├── api/
│
├── database/
│   └── maipi.sql
│
├── index.php
│
└── README.md
```

---

## 🗄 Modelo de Base de Datos

El sistema emplea una base de datos relacional optimizada para consultas académicas.

### Tablas principales

📌 PERFIL_ALUMNO

📌 PERFORMANCE_MATRICULA

📌 OFERTA_ACADEMICA

📌 GESTION_INCIDENCIAS

📌 USUARIOS

📌 FACULTADES

📌 ESCUELAS

---

## 🛠 Tecnologías

| Tecnología | Uso |
|------------|-----|
| 🐘 PHP 8 | Backend |
| 💾 MySQL | Base de Datos |
| 🟢 Vue.js | Frontend |
| 🎨 Bootstrap 4 | Diseño |
| 📈 Chart.js | Dashboards |
| 🔔 SweetAlert2 | Alertas |
| ⚡ AJAX | Comunicación |
| 🎯 FontAwesome | Iconografía |

---

## ⚙ Instalación

### Clonar el repositorio

```bash
git clone https://github.com/usuario/MAIPI.git
```

Entrar al proyecto

```bash
cd MAIPI
```

Crear la Base de Datos

```sql
CREATE DATABASE maipi;
```

Importar

```
database/maipi.sql
```

Configurar

```
config/db.php
```

Ejecutar

```
http://localhost/MAIPI
```

---

## 📊 Flujo del Sistema

```text
Estudiante

↓

Registro Académico

↓

Motor de Riesgo

↓

Clasificación

↓

Dashboard

↓

Intervención
```

---

## 👥 Equipo

| Integrante |
|------------|
| 👨‍💻 Jose Luis Garay Ramos |
| 👩‍💻 Claudia Escobar Champi |
| 👩‍💻 Camila Bautista |
| 👩‍💻 Fiorella Fuentes |
| 👩‍💻 Esther Condori |

---

## 📜 Licencia

Proyecto desarrollado con fines académicos para la **Universidad Nacional Agraria La Molina (UNALM)**.

---

<div align="center">

Desarrollado con ❤️ por el equipo **MAIPI**

</div>
