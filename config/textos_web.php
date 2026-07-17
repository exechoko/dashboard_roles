<?php

/*
|--------------------------------------------------------------------------
| Catálogo de textos editables de la web pública
|--------------------------------------------------------------------------
|
| Cada bloque editable se marca en el HTML con data-edit="<clave>" y se
| declara acá con su etiqueta amigable, tipo de campo y valor por defecto.
| Cada grupo representa una página ('pagina' = archivo .html) para que en el
| panel se vea claramente qué página se está editando.
| Para sumar un texto editable: marcá el elemento en el HTML y agregá una
| entrada en este catálogo. Solo texto plano (sin HTML interno).
|
*/

return [

    'home' => [
        'label'  => 'Inicio',
        'pagina' => 'index.html',
        'textos' => [
            'home.hero_subtitulo' => [
                'label'   => 'Subtítulo principal (hero)',
                'tipo'    => 'textarea',
                'default' => 'Cada segundo cuenta. Llamadas de emergencia, eventos en tiempo real, móviles geolocalizados y cámaras monitoreadas las 24 horas para proteger a la comunidad.',
            ],
            'home.command_titulo' => [
                'label'   => 'Centro de comando · título',
                'tipo'    => 'text',
                'default' => 'Una red operativa entre móviles, cámaras y ciudadanía.',
            ],
            'home.command_texto' => [
                'label'   => 'Centro de comando · texto',
                'tipo'    => 'textarea',
                'default' => 'Cuando ingresa una llamada al 911, cada segundo cuenta. Los operadores reciben el alerta, el despacho coordina por radio al móvil más cercano y las cámaras de videovigilancia siguen el evento en tiempo real. Todo el sistema trabaja en simultáneo para que la respuesta llegue rápido, donde se la necesita.',
            ],
            'home.comunidad_texto' => [
                'label'   => 'Compromiso con la Comunidad · texto',
                'tipo'    => 'textarea',
                'default' => 'La División 911 y Videovigilancia trabaja de manera permanente para mejorar la seguridad ciudadana, incorporando tecnología, optimizando procesos y fortaleciendo la articulación con otras instituciones. El compromiso es brindar una respuesta rápida, eficiente y profesional ante cada emergencia, contribuyendo al bienestar de toda la comunidad.',
            ],
        ],
    ],

    'estadisticas' => [
        'label'  => 'Estadísticas',
        'pagina' => 'estadisticas.html',
        'textos' => [
            'estadisticas.hero_titulo' => [
                'label'   => 'Título de la página',
                'tipo'    => 'text',
                'default' => 'ESTADÍSTICAS',
            ],
            'estadisticas.hero_subtitulo' => [
                'label'   => 'Subtítulo de la página',
                'tipo'    => 'text',
                'default' => 'Métricas y análisis de eventos · 16 may – 16 jun 2026',
            ],
        ],
    ],

    'historia' => [
        'label'  => 'Historia',
        'pagina' => 'historia.html',
        'textos' => [
            'historia.hero_titulo' => [
                'label'   => 'Título de la página',
                'tipo'    => 'text',
                'default' => 'Nuestra historia',
            ],
            'historia.hero_subtitulo' => [
                'label'   => 'Subtítulo de la página',
                'tipo'    => 'textarea',
                'default' => 'Más de una década protegiendo a la comunidad con tecnología y compromiso',
            ],
        ],
    ],

    'tecnologia' => [
        'label'  => 'Tecnología',
        'pagina' => 'tecnologia.html',
        'textos' => [
            'tecnologia.hero_titulo' => [
                'label'   => 'Título de la página',
                'tipo'    => 'text',
                'default' => 'Tecnología aplicada',
            ],
            'tecnologia.hero_subtitulo' => [
                'label'   => 'Subtítulo de la página',
                'tipo'    => 'textarea',
                'default' => 'Sistemas y operaciones de la División 911 y V.V',
            ],
        ],
    ],

    'dependencias' => [
        'label'  => 'Dependencias',
        'pagina' => 'dependencias.html',
        'textos' => [
            'dependencias.hero_titulo' => [
                'label'   => 'Título de la página',
                'tipo'    => 'text',
                'default' => 'Dependencias y contactos',
            ],
        ],
    ],

    'violencia_genero' => [
        'label'  => 'Violencia de Género',
        'pagina' => 'violencia-genero.html',
        'textos' => [
            'vg.hero_titulo' => [
                'label'   => 'Título de la página',
                'tipo'    => 'text',
                'default' => 'Sección Violencia de Género',
            ],
            'vg.hero_subtitulo' => [
                'label'   => 'Subtítulo de la página',
                'tipo'    => 'textarea',
                'default' => 'Atención exclusiva y monitoreo 24/7 para la protección de víctimas',
            ],
            'vg.aviso' => [
                'label'   => 'Aviso destacado (caja roja superior)',
                'tipo'    => 'html',
                'default' => '<strong>El 911 atiende emergencias, no toma denuncias.</strong> Si estás en riesgo inmediato, llamá al 911 para que se acerque un móvil. La <strong>denuncia formal</strong> se realiza en comisaría, fiscalía o por el portal del STJER (violencia de género).',
            ],
            'vg.card1_titulo' => [
                'label'   => 'Tarjeta 1 · título',
                'tipo'    => 'text',
                'default' => 'ATENCIÓN ESPECIALIZADA',
            ],
            'vg.card1_contenido' => [
                'label'   => 'Tarjeta 1 · contenido',
                'tipo'    => 'html',
                'default' => '<p>La <strong>Sección Violencia de Género</strong> de la División 911 y Videovigilancia se dedica a la atención exclusiva de casos de violencia de género. Cuenta con monitoreo permanente las 24 horas, los 365 días del año, tanto de agresores como de víctimas, para la prevención de nuevos episodios de violencia.</p>',
            ],
            'vg.card2_titulo' => [
                'label'   => 'Tarjeta 2 · título',
                'tipo'    => 'text',
                'default' => 'COORDINACIÓN CON LA OVG - STJER',
            ],
            'vg.card2_contenido' => [
                'label'   => 'Tarjeta 2 · contenido',
                'tipo'    => 'html',
                'default' => <<<'HTML'
<p>La Sección trabaja en coordinación directa con la <strong>Oficina de Violencia de Género (OVG)</strong> del <strong>Superior Tribunal de Justicia de Entre Ríos</strong>, parte del <strong>Centro Judicial de Género "Dra. Carmen María Argibay"</strong> —que integra también la Oficina de la Mujer—. La OVG fue creada por <strong>Acuerdo General Nº 38/12</strong> en el marco del convenio con la Corte Suprema de Justicia de la Nación (2009) y de los tratados internacionales CEDAW y Belem do Pará.</p>
<p style="margin-top:0.8rem;">El STJER remite a la División las <strong>denuncias y medidas cautelares</strong> dictadas por el sistema judicial para su monitoreo y cumplimiento efectivo, en articulación con la Secretaría de Justicia y la Policía de Entre Ríos.</p>
<p style="margin-top:0.8rem;font-size:0.9rem;">Sitio oficial: <a href="https://www.jusentrerios.gov.ar/ovg/" target="_blank" rel="noopener noreferrer" style="color:var(--accent-cyan);">www.jusentrerios.gov.ar/ovg/</a></p>
HTML,
            ],
            'vg.card3_titulo' => [
                'label'   => 'Tarjeta 3 · título',
                'tipo'    => 'text',
                'default' => 'MONITOREO 24/7',
            ],
            'vg.card3_contenido' => [
                'label'   => 'Tarjeta 3 · contenido',
                'tipo'    => 'html',
                'default' => '<p>El sistema de monitoreo funciona de manera ininterrumpida, permitiendo el seguimiento en tiempo real de las medidas cautelares dictadas por el STJER. Ante cualquier incumplimiento o situación de riesgo, se activa de inmediato el protocolo de respuesta.</p>',
            ],
            'vg.dispositivos_titulo' => [
                'label'   => 'Título "Dispositivos en funcionamiento"',
                'tipo'    => 'text',
                'default' => 'Dispositivos en funcionamiento',
            ],
            'vg.card4_titulo' => [
                'label'   => 'Tarjeta 4 · título',
                'tipo'    => 'text',
                'default' => 'BOTÓN DE PÁNICO',
            ],
            'vg.card4_contenido' => [
                'label'   => 'Tarjeta 4 · contenido',
                'tipo'    => 'html',
                'default' => <<<'HTML'
<p>Aplicación móvil desarrollada por la Policía de Entre Ríos que permite generar una alerta inmediata hacia la sala de la Sección Violencia de Género. En cada activación, desde la sala se coordina y despachan recursos (patrullas, motopatrullas, recursos a pie) hacia el punto exacto de la víctima, para su protección inmediata y frenar al agresor. Además, se monitorean en tiempo real las cámaras de seguridad del lugar.</p>
<div style="background:rgba(0,229,255,0.05);border:1px solid rgba(0,229,255,0.2);border-radius:8px;padding:0.8rem 1rem;margin-top:1rem;">
    <p style="margin:0;font-size:0.88rem;">Protocolo aprobado por el STJER: <strong>Resolución Tribunalicia Nº 313/2018</strong> y <strong>Acuerdo General Nº 19/17, Punto 6º C)</strong>.</p>
</div>
HTML,
            ],
            'vg.card5_titulo' => [
                'label'   => 'Tarjeta 5 · título',
                'tipo'    => 'text',
                'default' => 'SISTEMA DUAL (TOBILLERA)',
            ],
            'vg.card5_contenido' => [
                'label'   => 'Tarjeta 5 · contenido',
                'tipo'    => 'html',
                'default' => <<<'HTML'
<p>Dispositivo electrónico que se coloca en el <strong>tobillo del agresor</strong> y permite monitorear su ubicación en tiempo real. Si se acerca a la víctima o incumple una medida de alejamiento, el sistema genera una alerta inmediata para la intervención de las fuerzas de seguridad.</p>
<div style="background:rgba(0,229,255,0.05);border:1px solid rgba(0,229,255,0.2);border-radius:8px;padding:0.8rem 1rem;margin-top:1rem;">
    <p style="margin:0;font-size:0.88rem;">Implementado en <strong>Paraná y Gran Paraná</strong> (San Benito y Oro Verde) con proyección a toda la provincia. Protocolo aprobado por <strong>Acuerdo General Nº 35/18, Punto 6º</strong>.</p>
</div>
HTML,
            ],
            'vg.card6_titulo' => [
                'label'   => 'Tarjeta 6 · título',
                'tipo'    => 'text',
                'default' => 'REJUCAV',
            ],
            'vg.card6_contenido' => [
                'label'   => 'Tarjeta 6 · contenido',
                'tipo'    => 'html',
                'default' => <<<'HTML'
<p>El <strong>Registro Judicial de Causas y Antecedentes de Violencia (REJUCAV)</strong> es un sistema web desarrollado y mantenido por el Poder Judicial de Entre Ríos, aprobado por <strong>Acuerdo General del 04.08.15</strong>, que funciona en el ámbito de la OVG. Todos los organismos del Poder Judicial —Juzgados de Familia, de Paz, Laborales, Penales, OGAs y Fiscalías— cargan datos de las causas de violencia, generando estadísticas que respaldan las políticas públicas en la materia.</p>
<p style="margin-top:0.8rem;font-size:0.9rem;">Informes: <a href="https://www.jusentrerios.gov.ar/rejucav-ovg/" target="_blank" rel="noopener noreferrer" style="color:var(--accent-cyan);">www.jusentrerios.gov.ar/rejucav-ovg/</a></p>
HTML,
            ],
            'vg.card7_titulo' => [
                'label'   => 'Tarjeta 7 · título',
                'tipo'    => 'text',
                'default' => 'MARCO NORMATIVO',
            ],
            'vg.card7_contenido' => [
                'label'   => 'Tarjeta 7 · contenido (marco normativo)',
                'tipo'    => 'html',
                'default' => <<<'HTML'
<h4 style="color:var(--accent-cyan);">Tratados internacionales</h4>
<ul style="margin-top:0.5rem;padding-left:1.5rem;">
    <li>CEDAW — Convención sobre la Eliminación de todas las Formas de Discriminación contra la Mujer</li>
    <li>Convención Interamericana de Belém do Pará</li>
</ul>
<h4 style="margin-top:1rem;color:var(--accent-cyan);">Leyes nacionales</h4>
<ul style="margin-top:0.5rem;padding-left:1.5rem;">
    <li>Ley 24.417 — Violencia Familiar</li>
    <li>Ley 26.485 — Protección Integral para Prevenir, Sancionar y Erradicar la Violencia contra las Mujeres</li>
    <li>Ley 27.499 — Ley Micaela (capacitación en perspectiva de género)</li>
</ul>
<h4 style="margin-top:1rem;color:var(--accent-cyan);">Leyes provinciales (Entre Ríos)</h4>
<ul style="margin-top:0.5rem;padding-left:1.5rem;">
    <li>Ley 9.198 — Violencia Familiar</li>
    <li>Ley 10.058 — Violencia contra la Mujer</li>
    <li>Ley 10.668 — Procedimientos de Familia (habilita la denuncia por correo electrónico)</li>
    <li>Ley 10.768 — Adhesión a la Ley Micaela</li>
</ul>
<h4 style="margin-top:1rem;color:var(--accent-cyan);">Protocolos y guías del STJER</h4>
<ul style="margin-top:0.5rem;padding-left:1.5rem;">
    <li>Protocolo de Actuación Judicial en causas de Violencia — Ac. Gral. 25/17</li>
    <li>Guía de Lenguaje Inclusivo Judicial — Ac. Gral. 03/19</li>
</ul>
HTML,
            ],
            'vg.card8_titulo' => [
                'label'   => 'Tarjeta 8 · título',
                'tipo'    => 'text',
                'default' => 'CÓMO DENUNCIAR',
            ],
            'vg.card8_contenido' => [
                'label'   => 'Tarjeta 8 · contenido (cómo denunciar)',
                'tipo'    => 'html',
                'default' => <<<'HTML'
<h4 style="color:var(--accent-cyan);">Denuncia de violencia de género</h4>
<p style="margin-top:0.5rem;">Podés denunciar si sufrís violencia o si querés denunciar en protección de otra persona víctima:</p>
<ul style="margin-top:0.5rem;padding-left:1.5rem;">
    <li><strong>Portal del STJER (online)</strong> — exclusivo para violencia de género contra la mujer y colectivo LGBTQ+: <a href="https://www.jusentrerios.gov.ar/denuncias/" target="_blank" rel="noopener noreferrer" style="color:var(--accent-cyan);">www.jusentrerios.gov.ar/denuncias/</a></li>
    <li><strong>Comisarías</strong> de la provincia</li>
    <li><strong>Fiscalía</strong> correspondiente</li>
    <li><strong>Sede de la OVG</strong> del STJER (también recibe denuncias por la Ley 10.668)</li>
</ul>
<div style="background:rgba(255,60,90,0.08);border:1px solid rgba(255,60,90,0.3);border-radius:8px;padding:0.9rem 1rem;margin-top:1rem;">
    <p style="margin:0;font-size:0.9rem;"><strong>El 911 atiende emergencias, no toma denuncias.</strong> Si estás en riesgo inmediato o necesitás que se acerque un móvil, llamá al 911. La denuncia formal la realizás por los canales anteriores.</p>
</div>
HTML,
            ],
            'vg.linea144_titulo' => [
                'label'   => 'Caja Línea 144 · título',
                'tipo'    => 'text',
                'default' => 'Línea 144',
            ],
            'vg.linea144_texto' => [
                'label'   => 'Caja Línea 144 · texto',
                'tipo'    => 'textarea',
                'default' => 'Línea nacional de atención a víctimas de violencia de género. Gratuita, 24 hs, los 365 días.',
            ],
            'vg.emergencias911_titulo' => [
                'label'   => 'Caja Emergencias 911 · título',
                'tipo'    => 'text',
                'default' => 'Emergencias 911',
            ],
            'vg.emergencias911_texto' => [
                'label'   => 'Caja Emergencias 911 · texto',
                'tipo'    => 'textarea',
                'default' => 'Riesgo inmediato, agresión en curso o incumplimiento de medida cautelar. Despacho de móvil las 24 hs.',
            ],
            'vg.enlaces_titulo' => [
                'label'   => 'Enlaces oficiales · título',
                'tipo'    => 'text',
                'default' => 'Enlaces oficiales',
            ],
            'vg.enlaces_lista' => [
                'label'   => 'Enlaces oficiales · lista',
                'tipo'    => 'html',
                'default' => <<<'HTML'
<li><a href="https://www.jusentrerios.gov.ar/ovg/" target="_blank" rel="noopener noreferrer" style="color:var(--accent-cyan);text-decoration:none;display:block;padding:0.7rem 1rem;border:1px solid var(--border);border-radius:10px;background:rgba(0,229,255,0.05);">Oficina de Violencia de Género (OVG)</a></li>
<li><a href="https://www.jusentrerios.gov.ar/institucional-ovg/" target="_blank" rel="noopener noreferrer" style="color:var(--accent-cyan);text-decoration:none;display:block;padding:0.7rem 1rem;border:1px solid var(--border);border-radius:10px;background:rgba(0,229,255,0.05);">Institucional OVG</a></li>
<li><a href="https://www.jusentrerios.gov.ar/rejucav-ovg/" target="_blank" rel="noopener noreferrer" style="color:var(--accent-cyan);text-decoration:none;display:block;padding:0.7rem 1rem;border:1px solid var(--border);border-radius:10px;background:rgba(0,229,255,0.05);">REJUCAV — Registro Judicial</a></li>
<li><a href="https://www.jusentrerios.gov.ar/normativa-de-genero-y-dh/" target="_blank" rel="noopener noreferrer" style="color:var(--accent-cyan);text-decoration:none;display:block;padding:0.7rem 1rem;border:1px solid var(--border);border-radius:10px;background:rgba(0,229,255,0.05);">Normativa de Género y Derechos Humanos</a></li>
<li><a href="https://www.jusentrerios.gov.ar/protocolo-de-violencia-ovg/" target="_blank" rel="noopener noreferrer" style="color:var(--accent-cyan);text-decoration:none;display:block;padding:0.7rem 1rem;border:1px solid var(--border);border-radius:10px;background:rgba(0,229,255,0.05);">Protocolos de violencia</a></li>
<li><a href="https://www.jusentrerios.gov.ar/consultas-frecuentes-ovg/" target="_blank" rel="noopener noreferrer" style="color:var(--accent-cyan);text-decoration:none;display:block;padding:0.7rem 1rem;border:1px solid var(--border);border-radius:10px;background:rgba(0,229,255,0.05);">Consultas frecuentes OVG</a></li>
HTML,
            ],
        ],
    ],

    'educacion' => [
        'label'  => 'Educación',
        'pagina' => 'educacion.html',
        'textos' => [
            'educacion.hero_titulo' => [
                'label'   => 'Título de la página',
                'tipo'    => 'text',
                'default' => 'Educación y Prevención',
            ],
            'educacion.hero_subtitulo' => [
                'label'   => 'Subtítulo de la página',
                'tipo'    => 'textarea',
                'default' => 'Recursos para la comunidad · Información que salva vidas',
            ],
            'educacion.intro' => [
                'label'   => 'Texto introductorio',
                'tipo'    => 'textarea',
                'default' => 'Conocé tus derechos, aprendé a prevenir situaciones de riesgo y accedé a recursos esenciales. Toda la información está basada en la legislación nacional y provincial vigente de Entre Ríos.',
            ],
            'educacion.tarjeta1_titulo' => [
                'label'   => 'Tarjeta 1 · título',
                'tipo'    => 'text',
                'default' => 'Seguridad Digital',
            ],
            'educacion.tarjeta1_texto' => [
                'label'   => 'Tarjeta 1 · texto',
                'tipo'    => 'textarea',
                'default' => 'Protegete de estafas online, phishing, grooming y ciberbullying. Aprendé a navegar de forma segura.',
            ],
            'educacion.tarjeta2_titulo' => [
                'label'   => 'Tarjeta 2 · título',
                'tipo'    => 'text',
                'default' => 'Primeros Auxilios',
            ],
            'educacion.tarjeta2_texto' => [
                'label'   => 'Tarjeta 2 · texto',
                'tipo'    => 'textarea',
                'default' => 'RCP, maniobra de Heimlich, heridas y emergencias médicas. Actuar a tiempo salva vidas.',
            ],
            'educacion.tarjeta3_titulo' => [
                'label'   => 'Tarjeta 3 · título',
                'tipo'    => 'text',
                'default' => 'Prevención de Violencia',
            ],
            'educacion.tarjeta3_texto' => [
                'label'   => 'Tarjeta 3 · texto',
                'tipo'    => 'textarea',
                'default' => 'Señales de alerta, tipos de violencia, recursos y cómo ayudar. Ley 26.485 y Ley 10.956 (ER).',
            ],
            'educacion.tarjeta4_titulo' => [
                'label'   => 'Tarjeta 4 · título',
                'tipo'    => 'text',
                'default' => 'Seguridad Vial',
            ],
            'educacion.tarjeta4_texto' => [
                'label'   => 'Tarjeta 4 · texto',
                'tipo'    => 'textarea',
                'default' => 'Alcohol cero al volante en Entre Ríos. Reglas, accidentes y conducción responsable.',
            ],
            'educacion.tarjeta5_titulo' => [
                'label'   => 'Tarjeta 5 · título',
                'tipo'    => 'text',
                'default' => 'Prevención del Delito',
            ],
            'educacion.tarjeta5_texto' => [
                'label'   => 'Tarjeta 5 · texto',
                'tipo'    => 'textarea',
                'default' => 'Robos, seguridad en el hogar, cajeros automáticos y estafas presenciales.',
            ],
            'educacion.tarjeta6_titulo' => [
                'label'   => 'Tarjeta 6 · título',
                'tipo'    => 'text',
                'default' => 'Adicciones',
            ],
            'educacion.tarjeta6_texto' => [
                'label'   => 'Tarjeta 6 · texto',
                'tipo'    => 'textarea',
                'default' => 'Recursos, líneas de ayuda y centros de atención. SEDRONAR: línea 141 gratuita y anónima.',
            ],
            'educacion.campanias_titulo' => [
                'label'   => 'Título "Campañas estacionales"',
                'tipo'    => 'text',
                'default' => 'Campañas estacionales',
            ],
            'educacion.campania1_titulo' => [
                'label'   => 'Campaña 1 · título',
                'tipo'    => 'text',
                'default' => 'Verano',
            ],
            'educacion.campania1_texto' => [
                'label'   => 'Campaña 1 · texto',
                'tipo'    => 'textarea',
                'default' => 'Prevención de ahogamientos, golpes de calor, protección solar y seguridad en rutas.',
            ],
            'educacion.campania2_titulo' => [
                'label'   => 'Campaña 2 · título',
                'tipo'    => 'text',
                'default' => 'Invierno',
            ],
            'educacion.campania2_texto' => [
                'label'   => 'Campaña 2 · texto',
                'tipo'    => 'textarea',
                'default' => 'Prevención de intoxicación por monóxido de carbono, estufas seguras y enfermedades respiratorias.',
            ],
            'educacion.campania3_titulo' => [
                'label'   => 'Campaña 3 · título',
                'tipo'    => 'text',
                'default' => 'Vuelta a Clases',
            ],
            'educacion.campania3_texto' => [
                'label'   => 'Campaña 3 · texto',
                'tipo'    => 'textarea',
                'default' => 'Seguridad en el camino a la escuela, bullying, ciberbullying y cuidados en el transporte.',
            ],
            'educacion.campania4_titulo' => [
                'label'   => 'Campaña 4 · título',
                'tipo'    => 'text',
                'default' => 'Fiestas',
            ],
            'educacion.campania4_texto' => [
                'label'   => 'Campaña 4 · texto',
                'tipo'    => 'textarea',
                'default' => 'Alcohol cero al volante, pirotecnia segura, estafas navideñas y cuidados en reuniones.',
            ],
            'educacion.legal_titulo' => [
                'label'   => 'Marco legal · título',
                'tipo'    => 'text',
                'default' => 'Marco legal de referencia',
            ],
            'educacion.legal_texto' => [
                'label'   => 'Marco legal · texto',
                'tipo'    => 'textarea',
                'default' => 'Toda la información de esta sección se basa en la legislación nacional y provincial vigente. Las leyes provinciales aplicables son específicamente las de la Provincia de Entre Ríos.',
            ],
            'educacion.legal_tags' => [
                'label'   => 'Marco legal · etiquetas de leyes',
                'tipo'    => 'html',
                'default' => <<<'HTML'
<span style="background:rgba(0,229,255,0.1);border:1px solid rgba(0,229,255,0.3);padding:0.5rem 1rem;border-radius:8px;font-size:0.85rem;">Ley 24.449 - Tránsito</span>
<span style="background:rgba(0,229,255,0.1);border:1px solid rgba(0,229,255,0.3);padding:0.5rem 1rem;border-radius:8px;font-size:0.85rem;">Ley 26.485 - Violencia</span>
<span style="background:rgba(0,229,255,0.1);border:1px solid rgba(0,229,255,0.3);padding:0.5rem 1rem;border-radius:8px;font-size:0.85rem;">Ley 26.061 - Niñez</span>
<span style="background:rgba(0,229,255,0.1);border:1px solid rgba(0,229,255,0.3);padding:0.5rem 1rem;border-radius:8px;font-size:0.85rem;">Ley 23.737 - Estupefacientes</span>
<span style="background:rgba(0,229,255,0.1);border:1px solid rgba(0,229,255,0.3);padding:0.5rem 1rem;border-radius:8px;font-size:0.85rem;">Ley 26.388 - Delitos Informáticos</span>
<span style="background:rgba(0,229,255,0.1);border:1px solid rgba(0,229,255,0.3);padding:0.5rem 1rem;border-radius:8px;font-size:0.85rem;">Ley 10.956 (ER) - Violencia Género</span>
<span style="background:rgba(0,229,255,0.1);border:1px solid rgba(0,229,255,0.3);padding:0.5rem 1rem;border-radius:8px;font-size:0.85rem;">Ley 9.198 (ER) - Violencia Familiar</span>
HTML,
            ],
        ],
    ],

    'faq' => [
        'label'  => 'Preguntas frecuentes',
        'pagina' => 'faq.html',
        'textos' => [
            'faq.hero_titulo' => [
                'label'   => 'Título de la página',
                'tipo'    => 'text',
                'default' => 'Preguntas frecuentes',
            ],
            'faq.hero_subtitulo' => [
                'label'   => 'Subtítulo de la página',
                'tipo'    => 'textarea',
                'default' => 'Resolvé tus dudas sobre el servicio de emergencias 911',
            ],
        ],
    ],

    'galeria' => [
        'label'  => 'Galería',
        'pagina' => 'galeria.html',
        'textos' => [
            'galeria.hero_titulo' => [
                'label'   => 'Título de la página',
                'tipo'    => 'text',
                'default' => 'Galería de imágenes',
            ],
            'galeria.hero_subtitulo' => [
                'label'   => 'Subtítulo de la página',
                'tipo'    => 'textarea',
                'default' => 'Conoce nuestras instalaciones y equipamiento',
            ],
        ],
    ],

    'totems' => [
        'label'  => 'Tótems',
        'pagina' => 'totems.html',
        'textos' => [
            'totems.hero_titulo' => [
                'label'   => 'Título de la página',
                'tipo'    => 'text',
                'default' => 'Tótems de Seguridad',
            ],
            'totems.hero_subtitulo' => [
                'label'   => 'Subtítulo de la página',
                'tipo'    => 'textarea',
                'default' => 'Puntos de alerta y comunicación directa con la División 911',
            ],
        ],
    ],

];
