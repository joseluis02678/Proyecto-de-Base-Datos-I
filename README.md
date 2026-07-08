# 🎓 MAIPI - Sistema de Permanencia Estudiantil (UNALM)

## 🎯 Descripción del Proyecto
**MAIPI** (Monitor de Alerta e Intervención de Permanencia Institucional) es una plataforma integral diseñada para la gestión académica en la **Universidad Nacional Agraria La Molina**. El sistema permite centralizar el monitoreo de indicadores de riesgo académico, socioeconómico y asistencia, facilitando una respuesta temprana por parte de las autoridades universitarias para mejorar los índices de permanencia y éxito estudiantil.

## Estructura del proyecto

´´´
Telco-Customer-Churn/
│
├── data/                       # Almacena los archivos .csv o .xlsx del proyecto
│   └── TelcoCustomerChurn.csv
├── notebooks/                  # Aquí va tu Jupyter Notebook (.ipynb) limpio y organizado
│   └── Customer_Churn_Analysis.ipynb
├── images/                     # Capturas de gráficos generados (ROC, Lift, Clusters)
│   ├── roc_curve.png
│   └── lift_chart.png
├── src/                        # Funciones auxiliares o scripts de Python reutilizables
│   └── utils.py
├── README.md                   # Descripción profesional del proyecto
├── requirements.txt            # Lista de dependencias (pandas, scikit-learn, etc.)
└── .gitignore                  # Para ignorar archivos pesados o sensibles
´´´

## 🛠️ Tecnologías y Stack
* **Backend:** PHP 8, PDO (MySQL)
* **Frontend:** Vue.js 2 (vía CDN), Bootstrap 4, FontAwesome
* **Visualización de Datos:** Chart.js
* **Base de Datos:** MySQL
* **UI/UX:** SweetAlert2 para notificaciones e interacción intuitiva.

## 🧠 Características Principales
* **Monitor de Riesgo Académico:** Cálculo dinámico de puntajes de riesgo (0-100%) basado en variables académicas (notas, asistencias, número de intentos por curso) y factores socioeconómicos.
* **Algoritmo de Riesgo:** Implementación de una lógica de ponderación que etiqueta a los alumnos según su nivel de prioridad (Crítico, Alto, Medio, Bajo).
* **Interfaz Full-Stack:** Integración fluida entre PHP (Backend) y Vue.js (Frontend) para la manipulación reactiva de datos sin recargar la página.
* **Dashboard Visual:** Paneles con gráficos dinámicos (Doughnut charts) para una lectura rápida del estado del estudiante.

## 📊 Arquitectura de Datos
El sistema utiliza una arquitectura relacional (RDBMS) optimizada para consultas complejas:
1. **PERFIL_ALUMNO:** Datos demográficos y de facultad.
2. **PERFORMANCE_MATRICULA:** Información de rendimiento por curso.
3. **OFERTA_ACADEMICA:** Catálogo de cursos.
4. **GESTION_INCIDENCIAS:** Historial de alertas y diagnósticos.

## 🚀 Cómo Implementar
1. **Configuración de base de datos:** Importa el esquema SQL en tu servidor MySQL local.
2. **Conexión:** Configura las credenciales en `config/db.php`.
3. **Servidor:** Ejecuta el proyecto en un servidor local (XAMPP, WAMP o Laragon).
4. **Acceso:** Navega a `index.php` para visualizar el dashboard de gestión.

## 👥 Autores
* **Jose Luis Garay Ramos**
* **Claudia Escobar Champi**
* **Camila Bautista**
* **Fiorella Fuentes**
* **Esther Condori**
