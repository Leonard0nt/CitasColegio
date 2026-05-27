<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reuniones de hoy</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <main class="container page">
        <div id="alert" class="alert hidden"></div>
        <section class="panel">
            <div class="section-header">
                <div>
                    <h2>Reuniones del día</h2>
                    <p>Vista directa sin inicio de sesión.</p>
                </div>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Profesor</th>
                            <th>Alumno</th>
                            <th>Apoderado</th>
                            <th>Hora</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="todayMeetingsBody">
                        <tr><td colspan="6">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <script src="assets/js/today-meetings.js"></script>
</body>
</html>