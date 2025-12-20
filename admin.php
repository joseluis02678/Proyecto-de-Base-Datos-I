<?php
// ==========================================================
// CONFIGURACIÓN INICIAL Y CONEXIÓN
// ==========================================================
require 'db.php'; // Usamos la misma conexión de antes

// ==========================================================
// 1. LÓGICA DE MANIPULACIÓN AJAX (API Endpoint)
// ==========================================================

// Si JavaScript envía una acción, el script actúa como un API
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    header('Content-Type: application/json');

    try {
        if ($action === 'filter_risk_list') {
            // Ejemplo de manipulación: filtrar la lista por promedio mínimo
            $min_promedio = isset($_GET['min_promedio']) ? floatval($_GET['min_promedio']) : 0;
            $max_tricas = isset($_GET['max_tricas']) ? intval($_GET['max_tricas']) : 3;

            // Actualizamos la consulta SQL de riesgo basándonos en los parámetros del usuario
            $sql_riesgo_filtrado = "
                SELECT 
                    a.id, a.codigo_matricula, a.nombres, a.apellidos,
                    e.codigo_admin as escuela,
                    ps.nivel_riesgo_social, ps.tiene_beca_comedor,
                    AVG(m.promedio_proyectado) as promedio_global,
                    AVG(m.asistencia_porcentaje) as asistencia_global,
                    SUM(CASE WHEN m.vez_cursado = 3 THEN 1 ELSE 0 END) as tricas
                FROM alumnos a
                JOIN escuelas e ON a.escuela_id = e.id
                LEFT JOIN perfil_socioeconomico ps ON a.id = ps.alumno_id
                JOIN matriculas m ON a.id = m.alumno_id
                GROUP BY a.id
                HAVING AVG(m.promedio_proyectado) < :promedio AND SUM(CASE WHEN m.vez_cursado = 3 THEN 1 ELSE 0 END) <= :tricas
                ORDER BY tricas DESC, promedio_global ASC
                LIMIT 50
            ";
            $stmt = $pdo->prepare($sql_riesgo_filtrado);
            $stmt->execute(['promedio' => $min_promedio, 'tricas' => $max_tricas]);
            $alumnos_filtrados = $stmt->fetchAll();

            $lista_filtrada = [];
            $riesgo_alto_count_filtrado = 0;

            // Recalculamos el score de deserción para la lista filtrada
            foreach ($alumnos_filtrados as $alu) {
                $score = 0;
                if ($alu['tricas'] > 0) $score += 50; 
                if ($alu['promedio_global'] < 10.5) $score += 20;
                if ($alu['asistencia_global'] < 70) $score += 20;
                if ($alu['nivel_riesgo_social'] == 'ALTO') $score += 10;
                
                $score = min(100, $score);
                if ($score > 60) $riesgo_alto_count_filtrado++;

                $lista_filtrada[] = [
                    'codigo' => $alu['codigo_matricula'],
                    'nombre' => $alu['apellidos'] . ', ' . $alu['nombres'],
                    'escuela' => $alu['escuela'],
                    'score' => $score,
                    'motivo' => ($alu['tricas'] > 0) ? 'REGLAMENTO (TRICA)' : (($alu['asistencia_global'] < 70) ? 'ABANDONO' : 'BAJO RENDIMIENTO'),
                    'accion' => ($score > 80) ? 'VISITA SOCIAL' : 'TUTORÍA ACADÉMICA'
                ];
            }

            echo json_encode(['list' => $lista_filtrada, 'high_risk_count' => $riesgo_alto_count_filtrado]);
            exit; // Detenemos la ejecución después de la respuesta AJAX
        }

    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
        exit;
    }
}

// ==========================================================
// 2. CÁLCULOS ESTÁTICOS INICIALES (Si no es petición AJAX)
// ==========================================================

// A. KPI PRINCIPALES (Indicadores Clave)
$sql_kpi = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN ps.nivel_riesgo_social = 'CRITICO' THEN 1 ELSE 0 END) as riesgo_social_alto,
        SUM(CASE WHEN ps.trabaja_actualmente = 1 THEN 1 ELSE 0 END) as trabajadores
    FROM alumnos a
    LEFT JOIN perfil_socioeconomico ps ON a.id = ps.alumno_id
";
$kpi = $pdo->query($sql_kpi)->fetch();

// B. DISTRIBUCIÓN POR ESCUELAS (Para gráfico de barras)
$sql_escuelas = "
    SELECT e.codigo_admin, COUNT(*) as cantidad
    FROM alumnos a
    JOIN escuelas e ON a.escuela_id = e.id
    GROUP BY e.codigo_admin
";
$dist_escuelas = $pdo->query($sql_escuelas)->fetchAll();

// C. CÁLCULO INICIAL DE LA LISTA DE RIESGO (La misma lógica, pero para el render inicial)
// Reutilizamos la lógica del paso anterior para la carga inicial, sin parámetros de filtro
$sql_riesgo_inicial = "
    SELECT 
        a.id, a.codigo_matricula, a.nombres, a.apellidos,
        e.codigo_admin as escuela,
        ps.nivel_riesgo_social, ps.tiene_beca_comedor,
        AVG(m.promedio_proyectado) as promedio_global,
        AVG(m.asistencia_porcentaje) as asistencia_global,
        SUM(CASE WHEN m.vez_cursado = 3 THEN 1 ELSE 0 END) as tricas
    FROM alumnos a
    JOIN escuelas e ON a.escuela_id = e.id
    LEFT JOIN perfil_socioeconomico ps ON a.id = ps.alumno_id
    JOIN matriculas m ON a.id = m.alumno_id
    GROUP BY a.id
    HAVING promedio_global < 11 OR asistencia_global < 75 OR tricas > 0
    ORDER BY tricas DESC, promedio_global ASC
    LIMIT 50
";
$alumnos_riesgo_inicial = $pdo->query($sql_riesgo_inicial)->fetchAll();

$lista_intervencion = [];
$riesgo_alto_count = 0;

foreach ($alumnos_riesgo_inicial as $alu) {
    // Algoritmo de Puntuación (0 a 100) - REPETICIÓN DE LÓGICA POR CÓMODIDAD DEL EJEMPLO
    $score = 0;
    if ($alu['tricas'] > 0) $score += 50;
    if ($alu['promedio_global'] < 10.5) $score += 20;
    if ($alu['asistencia_global'] < 70) $score += 20;
    if ($alu['nivel_riesgo_social'] == 'ALTO') $score += 10;
    
    $score = min(100, $score);
    if ($score > 60) $riesgo_alto_count++;

    $lista_intervencion[] = [
        'codigo' => $alu['codigo_matricula'],
        'nombre' => $alu['apellidos'] . ', ' . $alu['nombres'],
        'escuela' => $alu['escuela'],
        'score' => $score,
        'motivo' => ($alu['tricas'] > 0) ? 'REGLAMENTO (TRICA)' : (($alu['asistencia_global'] < 70) ? 'ABANDONO' : 'BAJO RENDIMIENTO'),
        'accion' => ($score > 80) ? 'VISITA SOCIAL' : 'TUTORÍA ACADÉMICA'
    ];
}

// Datos para JS (Carga inicial)
$json_escuelas = json_encode($dist_escuelas);
$json_lista = json_encode($lista_intervencion);
$json_riesgo_alto_count = json_encode($riesgo_alto_count);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SAT - UNALM | Panel Dinámico</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* [Estilos CSS omitidos para brevedad - son los mismos del paso anterior] */
        body { background: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { width: 250px; background: #343a40; min-height: 100vh; position: fixed; color: #c2c7d0; }
        .content { margin-left: 250px; padding: 20px; }
        .card-box { background: #fff; border-radius: 5px; box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2); margin-bottom: 20px; padding: 20px; }
        .bg-gradient-danger { background: linear-gradient(45deg,#dc3545,#ff6b6b); color: white; }
        .bg-gradient-warning { background: linear-gradient(45deg,#ffc107,#ffca2c); color: #1f2d3d; }
        .bg-gradient-info { background: linear-gradient(45deg,#17a2b8,#1fc8e3); color: white; }
        .table-responsive { font-size: 0.9rem; }
        .score-indicator { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 5px; }
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 20px; height: 20px; animation: spin 2s linear infinite; display: none; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="sidebar p-3">
    <h4 class="text-white mb-4"><i class="fas fa-shield-alt"></i> SAT UNALM</h4>
    <p class="text-muted small">SISTEMA DE ALERTA TEMPRANA</p>
</div>

<div class="content">
    <h2 class="mb-4 text-dark">Tablero de Control de Riesgo (Dinámico)</h2>
    
    <div class="row">
        <div class="col-md-3">
            <div class="card-box bg-white">
                <h6 class="text-muted text-uppercase">Población Total</h6>
                <h3><?php echo $kpi['total']; ?></h3>
                <small class="text-success"><i class="fas fa-check"></i> Data Base</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-box bg-gradient-danger">
                <h6 class="text-uppercase" style="opacity: 0.8">Riesgo Crítico (Filtro)</h6>
                <h3 id="highRiskCount"><?php echo $riesgo_alto_count; ?></h3>
                <small>Requieren Intervención Inmediata</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-box bg-gradient-warning">
                <h6 class="text-uppercase" style="opacity: 0.8">Riesgo Social (Fijo)</h6>
                <h3><?php echo $kpi['riesgo_social_alto']; ?></h3>
                <small>Vivienda precaria / Sin Beca</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-box bg-gradient-info">
                <h6 class="text-uppercase" style="opacity: 0.8">Estudian y Trabajan (Fijo)</h6>
                <h3><?php echo $kpi['trabajadores']; ?></h3>
                <small>Alta probabilidad de fatiga</small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card-box bg-light border">
                <h6 class="mb-2"><i class="fas fa-sliders-h"></i> **Manipulación de Datos en Tiempo Real**</h6>
                <div class="form-row">
                    <div class="col-md-4">
                        <label for="minPromedio" class="small">Promedio Global MÁXIMO (Bajo Rendimiento)</label>
                        <input type="number" id="minPromedio" class="form-control form-control-sm" value="11" step="0.5" min="0" max="20">
                    </div>
                    <div class="col-md-4">
                        <label for="maxTricas" class="small">Máximo de TRICAS permitido</label>
                        <select id="maxTricas" class="form-control form-control-sm">
                            <option value="3">3 (Default)</option>
                            <option value="0">0 (Solo 1ra vez)</option>
                            <option value="1">1 (Permite Bica)</option>
                            <option value="2">2 (Permite Trica)</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-primary btn-sm btn-block" onclick="filterRiskList()">
                            <i class="fas fa-redo-alt"></i> Aplicar Filtros (AJAX)
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card-box" style="min-height: 400px;">
                <h5 class="mb-3 border-bottom pb-2">
                    <i class="fas fa-clipboard-list text-danger"></i> Casos Prioritarios
                    <div id="loader" class="loader ml-2 d-inline-block"></div>
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>Código</th>
                                <th>Alumno</th>
                                <th>Escuela</th>
                                <th>Score Riesgo</th>
                                <th>Motivo Principal</th>
                                <th>Acción Sugerida</th>
                            </tr>
                        </thead>
                        <tbody id="tablaRiesgo">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-box" style="min-height: 400px;">
                <h5 class="mb-3">Mapa de Calor por Escuela</h5>
                <canvas id="facultyChart"></canvas>
            </div>
        </div>
    </div>

</div>

<script>
    // DATOS DE PHP A JS (Carga Inicial)
    const escuelasData = <?php echo $json_escuelas; ?>;
    let listaRiesgo = <?php echo $json_lista; ?>; // Ahora es una variable LET, ya que se manipulará
    let chartInstance = null; // Para poder actualizar el gráfico

    // 1. Funciones de Renderizado
    function renderRiskTable(data) {
        const tbody = document.getElementById('tablaRiesgo');
        tbody.innerHTML = ''; // Limpiar la tabla
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No se encontraron alumnos con los criterios de filtro.</td></tr>';
            return;
        }

        data.forEach(alumno => {
            let colorScore = 'bg-success';
            if(alumno.score > 50) colorScore = 'bg-warning';
            if(alumno.score > 75) colorScore = 'bg-danger';

            const row = `
                <tr>
                    <td><span class="font-weight-bold">${alumno.codigo}</span></td>
                    <td>${alumno.nombre}</td>
                    <td><span class="badge badge-light border">${alumno.escuela}</span></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="score-indicator ${colorScore}"></span>
                            <strong>${alumno.score}%</strong>
                        </div>
                    </td>
                    <td class="text-danger small font-weight-bold">${alumno.motivo}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-dark py-0" onclick="alert('Generando cita para: ${alumno.nombre}')">
                            ${alumno.accion}
                        </button>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    }

    function renderFacultyChart(data) {
        const ctx = document.getElementById('facultyChart').getContext('2d');
        const labels = data.map(e => e.codigo_admin);
        const counts = data.map(e => e.cantidad);

        if(chartInstance) {
            chartInstance.destroy(); // Destruye la instancia anterior para evitar duplicados
        }

        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Estudiantes Totales por Escuela',
                    data: counts,
                    backgroundColor: '#343a40'
                }]
            },
            options: { responsive: true }
        });
    }

    // 2. Función AJAX (El "Tiempo Real" / Reactivo)
    function filterRiskList() {
        const minPromedio = document.getElementById('minPromedio').value;
        const maxTricas = document.getElementById('maxTricas').value;
        const loader = document.getElementById('loader');
        
        loader.style.display = 'inline-block'; // Mostrar loader

        // Construir la URL para llamar al mismo archivo como API
        const url = `admin.php?action=filter_risk_list&min_promedio=${minPromedio}&max_tricas=${maxTricas}`;

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    alert('Error en el servidor: ' + data.error);
                    return;
                }
                
                // Actualizar la lista y el KPI de riesgo
                listaRiesgo = data.list;
                document.getElementById('highRiskCount').textContent = data.high_risk_count;

                renderRiskTable(listaRiesgo);
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                alert('Ocurrió un error al cargar los datos. Revisa la consola.');
            })
            .finally(() => {
                loader.style.display = 'none'; // Ocultar loader
            });
    }

    // 3. Inicialización al cargar la página
    document.addEventListener('DOMContentLoaded', () => {
        renderRiskTable(listaRiesgo);
        renderFacultyChart(escuelasData);
    });
</script>

</body>
</html>