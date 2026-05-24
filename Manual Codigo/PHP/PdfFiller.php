<?php
declare(strict_types=1);

/**
 * PdfFiller.php
 * Rellena el PDF de la Junta CyL con datos de la BD normalizada.
 * Recibe un array ya denormalizado (resultado de JOIN entre tablas).
 */
class PdfFiller
{
    private string $templatePath;

    private const FIELD_MAP = [
        'solicitante.documentoIdentificacion' => 13,
        'solicitante.apellido1'               => 14,
        'solicitante.apellido2'               => 15,
        'solicitante.nombre'                  => 16,
        'solicitante.provincia'               => 17,
        'solicitante.municipio'               => 18,
        'solicitante.localidad'               => 19,
        'solicitante.cp'                      => 20,
        'solicitante.tipovia'                 => 21,
        'solicitante.calle'                   => 22,
        'solicitante.numero'                  => 23,
        'solicitante.portal'                  => 24,
        'solicitante.escalera'                => 25,
        'solicitante.piso'                    => 26,
        'solicitante.puerta'                  => 27,
        'solicitante.telefono'                => 28,
        'solicitante.telefonomovil'           => 29,
        'solicitante.email'                   => 30,
        'solicitante.enCalidadDe'             => 194,
        'solicitante.tipoentidad'             => 200,
        'solicitante.genero'                  => 207,

        'representante.documentoIdentificacion' => 31,
        'representante.apellido1'               => 32,
        'representante.apellido2'               => 33,
        'representante.nombre'                  => 34,
        'representante.provincia'               => 35,
        'representante.municipio'               => 36,
        'representante.localidad'               => 37,
        'representante.cp'                      => 38,
        'representante.tipovia'                 => 39,
        'representante.calle'                   => 40,
        'representante.numero'                  => 41,
        'representante.portal'                  => 42,
        'representante.escalera'                => 43,
        'representante.piso'                    => 44,
        'representante.puerta'                  => 45,
        'representante.telefono'                => 46,
        'representante.telefonomovil'           => 47,
        'representante.email'                   => 48,

        'organizador.documentoIdentificacion'   => 49,
        'organizador.apellido1'                 => 50,
        'organizador.apellido2'                 => 51,
        'organizador.nombre'                    => 52,
        'organizador.provincia'                 => 53,
        'organizador.municipio'                 => 54,
        'organizador.localidad'                 => 55,
        'organizador.cp'                        => 56,
        'organizador.tipovia'                   => 57,
        'organizador.calle'                     => 58,
        'organizador.numero'                    => 59,
        'organizador.portal'                    => 60,
        'organizador.escalera'                  => 61,
        'organizador.piso'                      => 62,
        'organizador.puerta'                    => 63,
        'organizador.telefono'                  => 64,
        'organizador.telefonomovil'             => 65,
        'organizador.email'                     => 66,

        'coto.provincia' => 67,
        'coto.numcoto'   => 68,
    ];

    private const RADIO_KIDS = [
        'solicitante.enCalidadDe' => ['titular' => 195, 'arrendatario' => 196],
        'solicitante.tipoentidad' => ['fisica' => 201, 'juridica' => 202, 'sin_personalidad' => 203],
        'solicitante.genero'      => ['mujer' => 208, 'hombre' => 209],
    ];

    private const RADIO_VALUES = [
        'solicitante.enCalidadDe' => ['titular' => '/Titular', 'arrendatario' => '/Arrendatario'],
        'solicitante.tipoentidad' => ['fisica' => '/FISICA', 'juridica' => '/JURIDICA', 'sin_personalidad' => '/ENTIDAD'],
        'solicitante.genero'      => ['mujer' => '/MUJER', 'hombre' => '/HOMBRE'],
    ];

    public function __construct(string $templatePath)
    {
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Plantilla PDF no encontrada: $templatePath");
        }
        $this->templatePath = $templatePath;
    }

    /**
     * $data es el resultado del JOIN ya preparado por generar_pdf.php:
     * prefijos: int_ (interesado), rep_ (representante), org_ (organizador), coto_
     */
    public function generate(array $data): string
    {
        $pdfBytes = file_get_contents($this->templatePath);
        $updates  = [];

        $textFields = [
            'solicitante.documentoIdentificacion' => $data['int_dni']           ?? '',
            'solicitante.apellido1'               => $data['int_apellido1']     ?? '',
            'solicitante.apellido2'               => $data['int_apellido2']     ?? '',
            'solicitante.nombre'                  => $data['int_nombre']        ?? '',
            'solicitante.provincia'               => $data['int_provincia']     ?? '',
            'solicitante.municipio'               => $data['int_municipio']     ?? '',
            'solicitante.localidad'               => $data['int_municipio']     ?? '',
            'solicitante.cp'                      => $data['int_cp']            ?? '',
            'solicitante.tipovia'                 => $data['int_tipovia']       ?? '',
            'solicitante.calle'                   => $data['int_direccion']     ?? '',
            'solicitante.numero'                  => $data['int_numero']        ?? '',
            'solicitante.telefono'                => $data['int_telefono']      ?? '',
            'solicitante.telefonomovil'           => $data['int_telefonomovil'] ?? '',
            'solicitante.email'                   => $data['int_email']         ?? '',

            'representante.documentoIdentificacion' => $data['rep_dni']           ?? '',
            'representante.apellido1'               => $data['rep_apellido1']     ?? '',
            'representante.apellido2'               => $data['rep_apellido2']     ?? '',
            'representante.nombre'                  => $data['rep_nombre']        ?? '',
            'representante.provincia'               => $data['rep_provincia']     ?? '',
            'representante.municipio'               => $data['rep_municipio']     ?? '',
            'representante.cp'                      => $data['rep_cp']            ?? '',
            'representante.tipovia'                 => $data['rep_tipovia']       ?? '',
            'representante.calle'                   => $data['rep_direccion']     ?? '',
            'representante.numero'                  => $data['rep_numero']        ?? '',
            'representante.telefono'                => $data['rep_telefono']      ?? '',
            'representante.telefonomovil'           => $data['rep_telefonomovil'] ?? '',
            'representante.email'                   => $data['rep_email']         ?? '',

            'organizador.documentoIdentificacion'   => $data['org_dni']           ?? '',
            'organizador.apellido1'                 => $data['org_apellido1']     ?? '',
            'organizador.apellido2'                 => $data['org_apellido2']     ?? '',
            'organizador.nombre'                    => $data['org_nombre']        ?? '',
            'organizador.provincia'                 => $data['org_provincia']     ?? '',
            'organizador.municipio'                 => $data['org_municipio']     ?? '',
            'organizador.cp'                        => $data['org_cp']            ?? '',
            'organizador.tipovia'                   => $data['org_tipovia']       ?? '',
            'organizador.calle'                     => $data['org_direccion']     ?? '',
            'organizador.numero'                    => $data['org_numero']        ?? '',
            'organizador.telefono'                  => $data['org_telefono']      ?? '',
            'organizador.telefonomovil'             => $data['org_telefonomovil'] ?? '',
            'organizador.email'                     => $data['org_email']         ?? '',

            'coto.provincia' => $data['coto_provincia']     ?? '',
            'coto.numcoto'   => $data['coto_num_matricula'] ?? '',
        ];

        foreach ($textFields as $field => $value) {
            $objNum = self::FIELD_MAP[$field] ?? null;
            if ($objNum === null || $value === '') continue;
            $updates[$objNum] = ['V' => $this->encodePdfString($value)];
        }

        $radioFields = [
            'solicitante.enCalidadDe' => $data['decl_en_calidad_de'] ?? '',
            'solicitante.tipoentidad' => $data['decl_tipo_entidad']  ?? '',
            'solicitante.genero'      => $data['decl_genero']        ?? '',
        ];

        foreach ($radioFields as $field => $option) {
            if (empty($option)) continue;
            $parentObj = self::FIELD_MAP[$field] ?? null;
            if ($parentObj === null) continue;
            $onValue = self::RADIO_VALUES[$field][$option] ?? null;
            if ($onValue === null) continue;
            $updates[$parentObj] = ['V' => $onValue];
            foreach (self::RADIO_KIDS[$field] as $kidOption => $kidObj) {
                $updates[$kidObj] = ['AS' => $kidOption === $option ? $onValue : '/Off'];
            }
        }

        return $this->applyIncrementalUpdate($pdfBytes, $updates);
    }

    private function applyIncrementalUpdate(string $pdf, array $updates): string
    {
        if (empty($updates)) return $pdf;
        $xrefOffsets = $this->parseXref($pdf);
        $newObjects  = '';
        $newXrefEntries = [];

        foreach ($updates as $objNum => $fields) {
            $offset = $xrefOffsets[$objNum] ?? null;
            if ($offset === null) continue;
            $origDict = $this->extractObjectDict($pdf, $offset, $objNum);
            foreach ($fields as $key => $value) {
                $origDict = preg_replace('/\/' . preg_quote($key, '/') . '\s*(\(.*?\)|<.*?>|\/\S+|\[.*?\])/s', '', $origDict);
                $origDict = rtrim($origDict);
                if (str_ends_with($origDict, '>>')) {
                    $origDict = substr($origDict, 0, -2) . "\n/$key $value\n>>";
                }
            }
            $newXrefEntries[$objNum] = strlen($pdf) + strlen($newObjects);
            $newObjects .= "\n$objNum 0 obj\n$origDict\nendobj\n";
        }

        $naResult = $this->buildNeedAppearancesUpdate($pdf, $xrefOffsets, strlen($pdf) + strlen($newObjects), $newXrefEntries);
        $newObjects .= $naResult;
        $xrefOffset = strlen($pdf) + strlen($newObjects);
        $newXref    = $this->buildXref($newXrefEntries);
        $prevOffset = $this->findLastXrefOffset($pdf);
        $size       = count($xrefOffsets) + 1;
        return $pdf . $newObjects . $newXref . "trailer\n<<\n/Size $size\n/Prev $prevOffset\n>>\nstartxref\n$xrefOffset\n%%EOF\n";
    }

    private function buildNeedAppearancesUpdate(string $pdf, array $xrefOffsets, int $base, array &$xrefEntries): string
    {
        if (!preg_match('/\/Root\s+(\d+)\s+0\s+R/', $pdf, $m)) return '';
        $rootOffset = $xrefOffsets[(int)$m[1]] ?? null;
        if (!$rootOffset) return '';
        $rootDict = $this->extractObjectDict($pdf, $rootOffset, (int)$m[1]);
        if (!preg_match('/\/AcroForm\s+(\d+)\s+0\s+R/', $rootDict, $m2)) return '';
        $acroNum    = (int)$m2[1];
        $acroOffset = $xrefOffsets[$acroNum] ?? null;
        if (!$acroOffset) return '';
        $acroDict = $this->extractObjectDict($pdf, $acroOffset, $acroNum);
        $acroDict = preg_replace('/\/NeedAppearances\s*(true|false)/', '', $acroDict);
        $acroDict = rtrim($acroDict);
        if (str_ends_with($acroDict, '>>')) {
            $acroDict = substr($acroDict, 0, -2) . "\n/NeedAppearances true\n>>";
        }
        $xrefEntries[$acroNum] = $base;
        return "\n$acroNum 0 obj\n$acroDict\nendobj\n";
    }

    private function parseXref(string $pdf): array
    {
        $offsets = [];
        preg_match_all('/\bxref\b\s*\n(\d+)\s+(\d+)\s*\n(.*?)(?=trailer)/s', $pdf, $matches, PREG_SET_ORDER);
        foreach ($matches as $section) {
            $startObj = (int)$section[1];
            $entries  = preg_split('/\r?\n/', trim($section[3]));
            foreach ($entries as $i => $entry) {
                if (preg_match('/^(\d{10})\s+(\d{5})\s+([fn])/', $entry, $em)) {
                    if ($em[3] === 'n' && (int)$em[1] > 0) $offsets[$startObj + $i] = (int)$em[1];
                }
            }
        }
        return $offsets;
    }

    private function extractObjectDict(string $pdf, int $offset, int $objNum): string
    {
        $start = strpos($pdf, "$objNum 0 obj", $offset);
        if ($start === false) return '<<>>';
        $start = strpos($pdf, '<<', $start);
        if ($start === false) return '<<>>';
        $depth = 0; $end = $start; $len = strlen($pdf);
        while ($end < $len) {
            if ($pdf[$end] === '<' && isset($pdf[$end+1]) && $pdf[$end+1] === '<') { $depth++; $end += 2; }
            elseif ($pdf[$end] === '>' && isset($pdf[$end+1]) && $pdf[$end+1] === '>') { $depth--; $end += 2; if ($depth === 0) break; }
            else $end++;
        }
        return substr($pdf, $start, $end - $start);
    }

    private function buildXref(array $entries): string
    {
        ksort($entries);
        $xref = "xref\n";
        foreach ($entries as $objNum => $offset) {
            $xref .= "$objNum 1\n" . sprintf("%010d 00000 n \n", $offset);
        }
        return $xref;
    }

    private function findLastXrefOffset(string $pdf): int
    {
        preg_match_all('/startxref\s+(\d+)/', $pdf, $m);
        return empty($m[1]) ? 0 : (int)end($m[1]);
    }

    private function encodePdfString(string $value): string
    {
        if ($value === '') return '()';
        foreach (str_split($value) as $b) {
            if (ord($b) > 127) {
                return '<' . strtoupper(bin2hex("\xFE\xFF" . mb_convert_encoding($value, 'UTF-16BE', 'UTF-8'))) . '>';
            }
        }
        return '(' . str_replace(['\\','(',')'], ['\\\\','\\(','\\)'], $value) . ')';
    }
}
