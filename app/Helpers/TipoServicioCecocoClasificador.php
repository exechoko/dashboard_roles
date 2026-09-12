<?php

namespace App\Helpers;

/**
 * Clasifica el tipo_servicio de un evento CECOCO en un nivel de gravedad,
 * para poder pintarlo con el mismo color en cualquier pantalla (desktop
 * usa esta misma lista de palabras clave directamente en
 * resources/views/eventos-cecoco/index.blade.php).
 */
class TipoServicioCecocoClasificador
{
    /**
     * @return string Clase de badge/chip: danger, warning, info, secondary, success o primary.
     */
    public static function badgeClass(?string $tipoServicio): string
    {
        $tipoLower = strtolower($tipoServicio ?? '');

        // NIVEL 1: CRÍTICO (Rojo)
        if (str_contains($tipoLower, 'incendio') || str_contains($tipoLower, 'fuego') ||
            str_contains($tipoLower, 'herido con arma') || str_contains($tipoLower, 'persona armada') ||
            str_contains($tipoLower, 'persona fallecida') || str_contains($tipoLower, 'abuso de arma') ||
            str_contains($tipoLower, 'violencia de genero con detenidos') || str_contains($tipoLower, 'tentativa de suicidio') ||
            str_contains($tipoLower, 'persona ajena en los fondos') || str_contains($tipoLower, 'solicitud de ambulancia') ||
            str_contains($tipoLower, 'accidente de transito con fallecido') || str_contains($tipoLower, 'accidente de transito con lesionados')) {
            return 'danger';
        }

        // NIVEL 2: URGENTE (Naranja)
        if (str_contains($tipoLower, 'accidente') || str_contains($tipoLower, 'amenazas') ||
            str_contains($tipoLower, 'alarma activada') || str_contains($tipoLower, 'persona extraviada') ||
            str_contains($tipoLower, 'persona tirada en la via publica') || str_contains($tipoLower, 'lesiones') ||
            str_contains($tipoLower, 'violacion de domicilio') || str_contains($tipoLower, 'violencia de genero') ||
            str_contains($tipoLower, 'tentativa de arrebato') || str_contains($tipoLower, 'tentativa de hurto') ||
            str_contains($tipoLower, 'tentativa de robo') || str_contains($tipoLower, 'tentativa de estafa') ||
            str_contains($tipoLower, 'hurto') || str_contains($tipoLower, 'robo') ||
            str_contains($tipoLower, 'arrebato') || str_contains($tipoLower, 'estafa') ||
            str_contains($tipoLower, 'usurpacion') || str_contains($tipoLower, 'sustraccion') ||
            str_contains($tipoLower, 'detencion') || str_contains($tipoLower, 'secuestro de elementos') ||
            str_contains($tipoLower, 'derrame quimicos') || str_contains($tipoLower, 'ebrios')) {
            return 'warning';
        }

        // NIVEL 3: IMPORTANTE (Azul)
        if (str_contains($tipoLower, 'aviso') || str_contains($tipoLower, 'animales sueltos') ||
            str_contains($tipoLower, 'daños') || str_contains($tipoLower, 'ruidos molestos') ||
            str_contains($tipoLower, 'elementos abandonados') || str_contains($tipoLower, 'cuidacoches') ||
            str_contains($tipoLower, 'problemas entre vecinos') || str_contains($tipoLower, 'problemas familiares') ||
            str_contains($tipoLower, 'maltrato animal') || str_contains($tipoLower, 'pedido de captura') ||
            str_contains($tipoLower, 'pedido de localizacion') || str_contains($tipoLower, 'persona en actitud sospechosa') ||
            str_contains($tipoLower, 'allanamiento') || str_contains($tipoLower, 'corte de calle') ||
            str_contains($tipoLower, 'desorden en la via publica') || str_contains($tipoLower, 'delitos contra la honestidad') ||
            str_contains($tipoLower, 'portacion de arma blanca') || str_contains($tipoLower, 'tiroteo') ||
            str_contains($tipoLower, 'inclemencias climaticas')) {
            return 'info';
        }

        // NIVEL 4: MODERADO (Gris)
        if (str_contains($tipoLower, 'colaboracion') || str_contains($tipoLower, 'informa datos') ||
            str_contains($tipoLower, 'llamada falsa') || str_contains($tipoLower, 'broma') ||
            str_contains($tipoLower, 'no responde') || str_contains($tipoLower, 'reiteracion de llamada') ||
            str_contains($tipoLower, 'equivocado') || str_contains($tipoLower, 'insulto') ||
            str_contains($tipoLower, 'correcta identificacion') || str_contains($tipoLower, 'recepcion sospechosa') ||
            str_contains($tipoLower, 'servicio bancario')) {
            return 'secondary';
        }

        // NIVEL 5: LEVE (Verde)
        if (str_contains($tipoLower, 'consulta') || str_contains($tipoLower, 'psicologico')) {
            return 'success';
        }

        return 'primary';
    }
}
