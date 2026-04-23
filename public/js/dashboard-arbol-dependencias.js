/**
 * Árbol jerárquico de Equipos por Dependencia (ECharts) con estilo neon en tema oscuro.
 *
 * Inputs esperados en window:
 *   - window.ARBOL_DATA         : objeto con el árbol ({id,name,value,propio,tipo,children,...})
 *   - window.ARBOL_EXPORT_URL   : URL base del export Excel (ej. route('flota.busquedaAvanzada.export'))
 *
 * Requiere ECharts 5 (cargado por CDN).
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('equiposArbolChart');
        if (!el || typeof echarts === 'undefined') return;

        var arbolData = window.ARBOL_DATA || null;
        if (!arbolData) return;

        var baseExportUrl = window.ARBOL_EXPORT_URL || '';
        var btnExport = document.getElementById('btn-arbol-exportar');
        var btnReset  = document.getElementById('btn-arbol-reset');
        var lblSel    = document.getElementById('arbol-seleccion-label');
        var selectedId   = null;
        var selectedName = null;

        function updateExport() {
            if (!btnExport) return;
            if (selectedId) {
                btnExport.href = baseExportUrl + '?destino_id%5B%5D=' + encodeURIComponent(selectedId);
                if (lblSel) lblSel.textContent = 'Filtro: ' + selectedName;
                if (btnReset) btnReset.style.display = '';
            } else {
                btnExport.href = baseExportUrl;
                if (lblSel) lblSel.textContent = '';
                if (btnReset) btnReset.style.display = 'none';
            }
        }

        // Paleta neon por nivel de profundidad
        var NEON_PALETTE = ['#22d3ee', '#a78bfa', '#f472b6', '#fb923c', '#34d399', '#60a5fa', '#facc15'];
        function colorPorNodo(p) {
            var depth = Math.max(0, (p.treeAncestors ? p.treeAncestors.length - 1 : 0));
            return NEON_PALETTE[depth % NEON_PALETTE.length];
        }

        function detectDark() {
            var html = document.documentElement;
            var t = html.getAttribute('data-theme') || html.getAttribute('data-bs-theme') || '';
            if (t === 'dark') return true;
            var bg = getComputedStyle(document.body).backgroundColor || '';
            var m = bg.match(/\d+/g);
            if (m && m.length >= 3) {
                var brightness = (parseInt(m[0]) + parseInt(m[1]) + parseInt(m[2])) / 3;
                return brightness < 128;
            }
            return false;
        }

        var chart = echarts.init(el);

        function buildOption(isDark) {
            var textColor       = isDark ? '#e0f2fe' : '#0f172a';
            var leafColor       = isDark ? '#f5d0fe' : '#334155';
            var tooltipBg       = isDark ? 'rgba(15,23,42,0.95)' : 'rgba(255,255,255,0.97)';
            var tooltipBorder   = isDark ? '#22d3ee' : '#cbd5e1';
            var lineColor       = isDark ? 'rgba(34,211,238,0.55)' : '#94a3b8';
            // En oscuro: halo oscuro detrás del texto para legibilidad tipo neon.
            // En claro: sin borde para evitar el aspecto "outline" blanco.
            var textBorderColor = isDark ? 'rgba(2,6,23,0.85)' : 'transparent';
            var textBorderWidth = isDark ? 3 : 0;
            var nodeShadowColor = isDark ? 'rgba(34,211,238,0.65)' : 'transparent';
            var nodeShadowBlur  = isDark ? 10 : 0;

            return {
                backgroundColor: 'transparent',
                tooltip: {
                    trigger: 'item',
                    backgroundColor: tooltipBg,
                    borderColor: tooltipBorder,
                    borderWidth: 1,
                    textStyle: { color: textColor, fontSize: 12 },
                    extraCssText: isDark
                        ? 'box-shadow: 0 0 18px rgba(34,211,238,0.35); border-radius: 8px;'
                        : 'box-shadow: 0 4px 12px rgba(0,0,0,0.12); border-radius: 8px;',
                    formatter: function (p) {
                        var d = p.data || {};
                        var accent = isDark ? '#22d3ee' : '#2563eb';
                        return '<div style="font-weight:600;color:' + accent + '">' + (d.name || '') + '</div>' +
                            (d.tipo ? '<div style="opacity:.7;font-size:11px;text-transform:capitalize">' + d.tipo + '</div>' : '') +
                            '<div style="margin-top:4px">Sub&aacute;rbol: <b>' + (d.value != null ? d.value : 0) + '</b></div>' +
                            '<div>Propios: <b>' + (d.propio != null ? d.propio : 0) + '</b></div>';
                    }
                },
                series: [{
                    type: 'tree',
                    data: [arbolData],
                    top: '2%',
                    left: '10%',
                    bottom: '2%',
                    right: '22%',
                    symbol: 'circle',
                    symbolSize: function (_, p) {
                        var v = (p && p.data && p.data.value) || 0;
                        return Math.min(22, 7 + Math.sqrt(v) * 0.8);
                    },
                    orient: 'LR',
                    initialTreeDepth: 2,
                    roam: true,
                    expandAndCollapse: true,
                    animationDuration: 400,
                    animationDurationUpdate: 500,
                    itemStyle: {
                        color: colorPorNodo,
                        borderColor: isDark ? '#0f172a' : '#ffffff',
                        borderWidth: 2,
                        shadowBlur: nodeShadowBlur,
                        shadowColor: nodeShadowColor
                    },
                    lineStyle: {
                        color: lineColor,
                        width: 1.4,
                        curveness: 0.5,
                        shadowBlur: isDark ? 6 : 0,
                        shadowColor: isDark ? 'rgba(34,211,238,0.35)' : 'transparent'
                    },
                    label: {
                        position: 'left',
                        verticalAlign: 'middle',
                        align: 'right',
                        color: textColor,
                        fontSize: 11,
                        fontWeight: 500,
                        textBorderColor: textBorderColor,
                        textBorderWidth: textBorderWidth,
                        textShadowColor: isDark ? 'rgba(34,211,238,0.45)' : 'transparent',
                        textShadowBlur: isDark ? 6 : 0,
                        formatter: function (p) {
                            var v = p.data.value != null ? p.data.value : 0;
                            return '{n|' + (p.data.name || '') + '}  {v|' + v + '}';
                        },
                        rich: {
                            n: { color: textColor, fontWeight: 500 },
                            v: {
                                color: isDark ? '#22d3ee' : '#2563eb',
                                fontWeight: 700,
                                padding: [0, 4],
                                backgroundColor: isDark ? 'rgba(34,211,238,0.12)' : 'rgba(37,99,235,0.08)',
                                borderRadius: 4
                            }
                        }
                    },
                    leaves: {
                        label: {
                            position: 'right',
                            verticalAlign: 'middle',
                            align: 'left',
                            color: leafColor,
                            textBorderColor: textBorderColor,
                            textBorderWidth: textBorderWidth,
                            textShadowColor: isDark ? 'rgba(244,114,182,0.5)' : 'transparent',
                            textShadowBlur: isDark ? 6 : 0
                        }
                    },
                    emphasis: {
                        focus: 'descendant',
                        itemStyle: {
                            shadowBlur: isDark ? 20 : 10,
                            shadowColor: isDark ? '#22d3ee' : 'rgba(37,99,235,0.4)'
                        },
                        lineStyle: {
                            width: 2.5,
                            color: isDark ? '#f472b6' : '#2563eb'
                        },
                        label: {
                            fontWeight: 700,
                            color: isDark ? '#ffffff' : '#0f172a'
                        }
                    }
                }]
            };
        }

        function render() {
            chart.setOption(buildOption(detectDark()), true);
        }
        render();

        // Doble clic = fija filtro para la exportacion de ese subarbol.
        // Clic simple = expandir/colapsar (comportamiento nativo ECharts).
        chart.on('dblclick', function (params) {
            if (!params || !params.data) return;
            if (params.data.id) {
                selectedId   = params.data.id;
                selectedName = params.data.name;
            } else {
                selectedId   = null;
                selectedName = null;
            }
            updateExport();
        });

        if (btnReset) {
            btnReset.addEventListener('click', function () {
                selectedId   = null;
                selectedName = null;
                updateExport();
            });
        }

        window.addEventListener('resize', function () { chart.resize(); });
        var tabTrigger = document.querySelector('a[data-toggle="tab"][href="#recursos3"]');
        if (tabTrigger) {
            tabTrigger.addEventListener('shown.bs.tab', function () { chart.resize(); });
            if (window.jQuery) jQuery(tabTrigger).on('shown.bs.tab', function () { chart.resize(); });
        }

        // Re-renderizar al cambiar el tema
        var themeObs = new MutationObserver(function (muts) {
            for (var i = 0; i < muts.length; i++) {
                var m = muts[i];
                if (m.type === 'attributes' && (m.attributeName === 'data-theme' || m.attributeName === 'data-bs-theme')) {
                    render();
                    return;
                }
            }
        });
        themeObs.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-theme', 'data-bs-theme']
        });

        updateExport();
    });
})();
