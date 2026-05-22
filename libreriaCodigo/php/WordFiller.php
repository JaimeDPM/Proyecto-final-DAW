<?php
declare(strict_types=1);

/**
 * WordFiller.php
 *
 * Rellena plantillas Word (.docx) reemplazando marcadores {{clave}}
 * por los datos de la BD. Sin dependencias externas — PHP puro.
 *
 * Un .docx es un ZIP con XML dentro. Esta clase:
 *   1. Lee el ZIP de la plantilla en memoria.
 *   2. Extrae word/document.xml.
 *   3. Normaliza los marcadores (Word a veces los parte en varios <w:r>).
 *   4. Reemplaza {{clave}} por el valor correspondiente.
 *   5. Devuelve el nuevo .docx como string binario.
 */
class WordFiller
{
    private string $templatePath;

    public function __construct(string $templatePath)
    {
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Plantilla Word no encontrada: $templatePath");
        }
        $this->templatePath = $templatePath;
    }

    /**
     * Genera el documento rellenado y lo devuelve como string binario.
     *
     * @param array $vars  Mapa ['{{clave}}' => 'valor', ...]
     */
    public function generate(array $vars): string
    {
        // Leer el ZIP en memoria
        $zip = new ZipArchive();
        if ($zip->open($this->templatePath) !== true) {
            throw new \RuntimeException("No se puede abrir la plantilla: {$this->templatePath}");
        }

        // Archivos XML donde pueden aparecer marcadores
        $xmlFiles = ['word/document.xml', 'word/header1.xml', 'word/footer1.xml'];

        $modifiedFiles = [];
        foreach ($xmlFiles as $xmlFile) {
            $content = $zip->getFromName($xmlFile);
            if ($content === false) continue;

            // Normalizar: unir marcadores que Word ha partido entre varios <w:r>
            $content = $this->normalizeMarkers($content);

            // Reemplazar marcadores
            $content = $this->replaceMarkers($content, $vars);

            $modifiedFiles[$xmlFile] = $content;
        }

        $zip->close();

        // Reconstruir el ZIP con los archivos modificados
        return $this->rebuildZip($modifiedFiles);
    }

    /**
     * Word a veces parte "{{nombre}}" en múltiples runs:
     *   <w:r><w:t>{{nom</w:t></w:r><w:r><w:t>bre}}</w:t></w:r>
     * Este método une el texto de runs consecutivos dentro de un párrafo
     * cuando detecta un marcador partido.
     */
    private function normalizeMarkers(string $xml): string
    {
        // Estrategia: extraer texto plano de cada párrafo, reconstruir si hay marcador partido
        // Más robusto: eliminar tags XML dentro de un marcador abierto
        // Patrón: {{ seguido de cualquier cosa (incluyendo tags XML) hasta }}
        return preg_replace_callback(
            '/\{\{[^}]*(?:<[^>]+>[^}]*)?\}\}/s',
            function ($m) {
                // Eliminar cualquier tag XML dentro del marcador
                return preg_replace('/<[^>]+>/', '', $m[0]);
            },
            $xml
        ) ?? $xml;
    }

    /**
     * Reemplaza los marcadores {{clave}} por sus valores,
     * escapando caracteres especiales XML.
     */
    private function replaceMarkers(string $xml, array $vars): string
    {
        foreach ($vars as $marker => $value) {
            $escaped = htmlspecialchars((string)$value, ENT_XML1, 'UTF-8');
            $xml = str_replace($marker, $escaped, $xml);
        }
        return $xml;
    }

    /**
     * Reconstruye el ZIP reemplazando los archivos modificados.
     * Devuelve el contenido binario del nuevo .docx.
     */
    private function rebuildZip(array $modifiedFiles): string
    {
        // Crear un archivo temporal
        $tmpPath = sys_get_temp_dir() . '/docx_' . uniqid() . '.docx';

        // Copiar la plantilla original al temporal
        copy($this->templatePath, $tmpPath);

        // Abrir el temporal y actualizar los archivos modificados
        $zip = new ZipArchive();
        if ($zip->open($tmpPath) !== true) {
            throw new \RuntimeException("No se puede crear el documento de salida");
        }

        foreach ($modifiedFiles as $filename => $content) {
            $zip->addFromString($filename, $content);
        }

        $zip->close();

        $result = file_get_contents($tmpPath);
        @unlink($tmpPath);

        if ($result === false) {
            throw new \RuntimeException("Error al leer el documento generado");
        }

        return $result;
    }
}
