<?php
require 'db.php';

// ==========================================================
// 1. BACKEND: LOGICA DE NEGOCIO Y CONSULTAS SQL
// ==========================================================

// Simulamos ID de alumno (En producción vendría del Login)
// Usamos el ID 1 o buscamos uno aleatorio que tenga datos
$id_alumno_simulado = 1;

// A. CONSULTA MAESTRA (JOIN de 4 Tablas: Alumno, Escuela, Facultad, Socioeconómico)
$sql_perfil = "
    SELECT 
        a.id, a.codigo_matricula, a.nombres, a.apellidos, a.anio_ingreso,
        e.nombre as escuela, e.codigo_admin as cod_escuela,
        f.nombre as facultad, f.siglas as sigla_facultad,
        ps.procedencia, ps.tipo_vivienda, ps.nivel_riesgo_social, ps.trabaja_actualmente, ps.tiene_beca_comedor
    FROM alumnos a
    JOIN escuelas e ON a.escuela_id = e.id
    JOIN facultades f ON e.facultad_id = f.id
    LEFT JOIN perfil_socioeconomico ps ON a.id = ps.alumno_id
    WHERE a.id = :id
";
$stmt = $pdo->prepare($sql_perfil);
$stmt->execute(['id' => $id_alumno_simulado]);
$perfil = $stmt->fetch();

if (!$perfil) die("<h3>Error:</h3> El alumno ID $id_alumno_simulado no existe. Ejecuta el script SQL de generación de datos primero.");

// B. CONSULTA ACADÉMICA (JOIN de 2 Tablas: Matrículas, Cursos)
$sql_notas = "
    SELECT 
        c.codigo, c.nombre, c.creditos, c.ciclo_sugerido, c.es_curso_filtro,
        m.nota_pc1, m.nota_parcial, m.promedio_proyectado, m.asistencia_porcentaje, m.vez_cursado
    FROM matriculas m
    JOIN cursos c ON m.curso_id = c.id
    WHERE m.alumno_id = :id
";
$stmt = $pdo->prepare($sql_notas);
$stmt->execute(['id' => $id_alumno_simulado]);
$cursos = $stmt->fetchAll();

// C. ALGORITMO DE RIESGO (Procesamiento Backend)
$stats = [
    'riesgo_score' => 0,
    'cursos_peligro' => 0,
    'cursos_total' => count($cursos),
    'promedio_ponderado' => 0
];

$suma_notas = 0;
$suma_creditos = 0;

// Transformamos la data para enviarla al JavaScript limpia
$cursos_frontend = [];

foreach ($cursos as $curso) {
    // 1. Calculamos ponderado
    if ($curso['promedio_proyectado']) {
        $suma_notas += ($curso['promedio_proyectado'] * $curso['creditos']);
        $suma_creditos += $curso['creditos'];
    }

    // 2. Detectamos Riesgo Individual
    $riesgo_curso = 'BAJO';
    $probabilidad_aprobacion = 90; // Base alta

    // Factores negativos
    if ($curso['vez_cursado'] > 1) {
        $probabilidad_aprobacion -= 20; // Bica quita 20%
        $stats['riesgo_score'] += 10;
    }
    if ($curso['vez_cursado'] == 3) {
        $stats['riesgo_score'] += 50; // TRICA ES CRÍTICO
        $riesgo_curso = 'CRITICO';
    }
    if ($curso['asistencia_porcentaje'] < 70) {
        $probabilidad_aprobacion -= 40; // Sin asistencia no pasas
        $stats['riesgo_score'] += 15;
        $riesgo_curso = 'ALTO';
    }
    if ($curso['promedio_proyectado'] < 10.5) {
        $probabilidad_aprobacion -= 30;
        $stats['cursos_peligro']++;
        if ($riesgo_curso != 'CRITICO') $riesgo_curso = 'MEDIO';
    }

    // Ajuste final
    $probabilidad_aprobacion = max(0, min(100, $probabilidad_aprobacion));

    $cursos_frontend[] = [
        'codigo' => $curso['codigo'],
        'nombre' => $curso['nombre'],
        'creditos' => $curso['creditos'],
        'pc1' => $curso['nota_pc1'] ?? '-',
        'parcial' => $curso['nota_parcial'] ?? '-',
        'promedio' => number_format($curso['promedio_proyectado'], 1),
        'asistencia' => $curso['asistencia_porcentaje'],
        'vez' => $curso['vez_cursado'],
        'probabilidad' => $probabilidad_aprobacion,
        'riesgo' => $riesgo_curso,
        'es_filtro' => $curso['es_curso_filtro']
    ];
}

// Cálculo final de ponderado
if ($suma_creditos > 0) {
    $stats['promedio_ponderado'] = number_format($suma_notas / $suma_creditos, 2);
}

// Factor Socioeconómico en el Riesgo Global
if ($perfil['nivel_riesgo_social'] == 'ALTO') $stats['riesgo_score'] += 20;
if ($perfil['trabaja_actualmente']) $stats['riesgo_score'] += 10;
if ($perfil['procedencia'] != 'LIMA' && $perfil['tipo_vivienda'] == 'CUARTO_ALQUILADO') $stats['riesgo_score'] += 10;

$stats['riesgo_score'] = min(100, $stats['riesgo_score']);

// D. JSON ENCODE (El Puente entre PHP y JavaScript)
$json_perfil = json_encode($perfil);
$json_cursos = json_encode($cursos_frontend);
$json_stats  = json_encode($stats);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAIPI - Sistema de Permanencia</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        /* ESTILO UNALM / MAIPI */
        body {
            background: #f0f3f4;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            font-size: 13px;
        }

        .app-aside {
            width: 240px;
            background: #2e3e4e;
            min-height: 100vh;
            position: fixed;
            color: #a6a8b1;
        }

        .app-content {
            margin-left: 240px;
            padding: 20px;
        }

        /* Sidebar */
        .brand {
            background: #1a2633;
            padding: 15px;
            text-align: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }

        .user-panel {
            padding: 15px;
            background: #232f3b;
            border-bottom: 1px solid #1a2633;
        }

        .user-panel h5 {
            color: #fad733;
            margin: 0;
            font-size: 14px;
            font-weight: 600;
        }

        .user-panel span {
            font-size: 11px;
            color: #85919d;
            display: block;
        }

        .nav-link {
            color: #b4b6bd;
            padding: 12px 20px;
            display: block;
            text-decoration: none;
            border-left: 3px solid transparent;
        }

        .nav-link:hover,
        .nav-link.active {
            background: #3a4b5d;
            color: white;
            border-left-color: #fad733;
        }

        .nav-header {
            padding: 15px 20px 5px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #5c6a77;
        }

        /* Cards */
        .card {
            border: none;
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border-radius: 3px;
        }

        .card-header {
            background: white;
            font-weight: 600;
            border-bottom: 1px solid #edf1f2;
            color: #58666e;
        }

        /* Utils */
        .text-unalm {
            color: #2e3e4e;
        }

        .badge-trica {
            background: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
        }

        .badge-bica {
            background: #ffc107;
            color: black;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
        }

        /* Loading overlay */
        [v-cloak] {
            display: none;
        }
    </style>
</head>

<body>

    <div id="app" v-cloak>
        <div class="app-aside">
            <div class="brand">
                <i class="fas fa-university mr-2"></i> MAIPI UNALM
            </div>
            <div class="user-panel">
                <h5>{{ perfil.apellidos }}, {{ perfil.nombres }}</h5>
                <span>{{ perfil.codigo_matricula }}</span>
                <span class="mt-1">{{ perfil.cod_escuela }} - {{ perfil.sigla_facultad }}</span>
            </div>

            <div class="mt-2">
                <a href="#" class="nav-link active"><i class="fas fa-chart-line fa-fw mr-2"></i> Monitor de Riesgo</a>
                <a href="#" class="nav-link"><i class="fas fa-calendar-alt fa-fw mr-2"></i> Horario</a>
                <div class="nav-header">Bienestar</div>
                <a href="#" class="nav-link"><i class="fas fa-utensils fa-fw mr-2"></i> Comedor</a>
                <a href="#" class="nav-link"><i class="fas fa-user-md fa-fw mr-2"></i> Centro Médico</a>
            </div>
        </div>

        <div class="app-content">

            <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm">
                <div>
                    <h4 class="m-0 text-unalm">Sistema de Permanencia Estudiantil</h4>
                    <small class="text-muted">Ciclo 2025-II &bull; Semana 8</small>
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-primary" @click="imprimirReporte">
                        <i class="fas fa-print"></i> Imprimir Reporte
                    </button>
                </div>
            </div>

            <div v-if="stats.riesgo_score > 50" class="alert alert-danger shadow-sm border-0 mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle fa-2x mr-3"></i>
                    <div>
                        <h6 class="m-0 font-weight-bold">Situación Académica Crítica Detectada</h6>
                        <p class="m-0 small">Tu puntaje de riesgo es <strong>{{ stats.riesgo_score }}/100</strong>. Tienes cursos en peligro de repitencia.</p>
                    </div>
                    <button class="btn btn-light btn-sm ml-auto text-danger font-weight-bold">Contactar Tutor</button>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header">
                            Resumen del Estudiante
                        </div>
                        <div class="card-body text-center">
                            <div style="height: 180px; width: 180px; margin: 0 auto; position: relative;">
                                <canvas id="riskChart"></canvas>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                    <h3 class="m-0 font-weight-bold">{{ stats.riesgo_score }}%</h3>
                                    <small class="text-muted">Riesgo</small>
                                </div>
                            </div>

                            <hr>

                            <div class="row text-left mt-3">
                                <div class="col-6 mb-2">
                                    <small class="text-muted d-block">Promedio Pond.</small>
                                    <span class="font-weight-bold h5">{{ stats.promedio_ponderado }}</span>
                                </div>
                                <div class="col-6 mb-2">
                                    <small class="text-muted d-block">Procedencia</small>
                                    <span class="font-weight-bold">{{ perfil.procedencia }}</span>
                                </div>
                                <div class="col-6 mb-2">
                                    <small class="text-muted d-block">Beca Comedor</small>
                                    <span v-if="perfil.tiene_beca_comedor == 1" class="badge badge-success">ACTIVA</span>
                                    <span v-else class="badge badge-secondary">NO TIENE</span>
                                </div>
                                <div class="col-6 mb-2">
                                    <small class="text-muted d-block">Situación Laboral</small>
                                    <span v-if="perfil.trabaja_actualmente == 1" class="badge badge-warning text-white">TRABAJA</span>
                                    <span v-else class="badge badge-light border">SOLO ESTUDIA</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Predicción de Rendimiento Académico</span>
                            <span class="badge badge-info">{{ stats.cursos_total }} Cursos Matriculados</span>
                        </div>
                        <div class="card-body p-0 table-responsive">
                            <table class="table table-hover mb-0" style="font-size: 13px;">
                                <thead class="bg-light text-muted">
                                    <tr>
                                        <th class="pl-4">Asignatura</th>
                                        <th class="text-center">Vez</th>
                                        <th class="text-center">Notas (PC1/Parcial)</th>
                                        <th class="text-center">Asistencia</th>
                                        <th class="text-center">Prom. Proy.</th>
                                        <th class="text-center">Prob. Aprobar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="curso in cursos">
                                        <td class="pl-4">
                                            <div class="font-weight-bold text-dark">{{ curso.codigo }} - {{ curso.nombre }}</div>
                                            <small v-if="curso.es_filtro == 1" class="text-danger"><i class="fas fa-fire"></i> Curso Filtro</small>
                                            <small v-else class="text-muted">{{ curso.creditos }} créditos</small>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span v-if="curso.vez == 3" class="badge-trica">TRICA</span>
                                            <span v-else-if="curso.vez == 2" class="badge-bica">BICA</span>
                                            <span v-else class="badge badge-light border">1ra</span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="d-block">{{ curso.pc1 }} / {{ curso.parcial }}</span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="progress" style="height: 5px; width: 60px; margin: 0 auto;">
                                                <div class="progress-bar"
                                                    :class="curso.asistencia < 70 ? 'bg-danger' : 'bg-success'"
                                                    :style="{width: curso.asistencia + '%'}"></div>
                                            </div>
                                            <small>{{ curso.asistencia }}%</small>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="font-weight-bold"
                                                :class="curso.promedio < 10.5 ? 'text-danger' : 'text-success'">
                                                {{ curso.promedio }}
                                            </span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div v-if="curso.riesgo === 'CRITICO'" class="text-danger font-weight-bold">
                                                <i class="fas fa-times-circle"></i> CRÍTICO
                                            </div>
                                            <div v-else>
                                                <span class="h6 font-weight-bold">{{ curso.probabilidad }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 1. RECIBIMOS LOS DATOS DE PHP (JSON)
        const phpPerfil = <?php echo $json_perfil; ?>;
        const phpCursos = <?php echo $json_cursos; ?>;
        const phpStats = <?php echo $json_stats; ?>;

        // 2. INICIALIZAMOS VUE.JS
        new Vue({
            el: '#app',
            data: {
                perfil: phpPerfil,
                cursos: phpCursos,
                stats: phpStats
            },
            mounted() {
                this.renderChart();
            },
            methods: {
                imprimirReporte() {
                    window.print();
                },
                renderChart() {
                    // Configuración de Chart.js
                    const ctx = document.getElementById('riskChart').getContext('2d');

                    // Colores dinámicos según el riesgo
                    let colorRiesgo = '#28a745'; // Verde
                    if (this.stats.riesgo_score > 30) colorRiesgo = '#ffc107'; // Amarillo
                    if (this.stats.riesgo_score > 60) colorRiesgo = '#dc3545'; // Rojo

                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Riesgo Actual', 'Zona Segura'],
                            datasets: [{
                                data: [this.stats.riesgo_score, 100 - this.stats.riesgo_score],
                                backgroundColor: [colorRiesgo, '#e9ecef'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            cutout: '75%',
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: {
                                display: false
                            },
                            tooltips: {
                                enabled: false
                            }
                        }
                    });
                }
            }
        });
    </script>

</body>

</html>