{{--
    Planche de badges pour dompdf. Tailwind n'y tourne pas et dompdf ne charge
    rien de distant : cette vue porte son propre CSS, reprend a la main les
    tokens de tailwind.config.js et embarque Plus Jakarta Sans depuis
    resources/fonts (licence OFL, voir OFL.txt a cote des fichiers).

    Tout est positionne en millimetres : c'est la seule unite qui survit
    fidelement de l'ecran a l'imprimante. Les traits de coupe courent dans les
    marges, aux frontieres des badges ; un filet clair les prolonge sur la
    planche pour le coup de ciseaux.
--}}

@php $fontPath = str_replace('\\', '/', $fonts); @endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Badges bénévoles — {{ $edition->name }}</title>

    <style>
        @font-face { font-family: 'Plus Jakarta Sans'; font-weight: 400; src: url('{{ $fontPath }}/PlusJakartaSans-Regular.ttf') format('truetype'); }
        @font-face { font-family: 'Plus Jakarta Sans'; font-weight: 600; src: url('{{ $fontPath }}/PlusJakartaSans-SemiBold.ttf') format('truetype'); }
        @font-face { font-family: 'Plus Jakarta Sans'; font-weight: 700; src: url('{{ $fontPath }}/PlusJakartaSans-Bold.ttf') format('truetype'); }
        @font-face { font-family: 'Plus Jakarta Sans'; font-weight: 800; src: url('{{ $fontPath }}/PlusJakartaSans-ExtraBold.ttf') format('truetype'); }

        @page { margin: 0; }

        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', 'DejaVu Sans', sans-serif;
            color: #1F1A1C;
        }

        .sheet { position: relative; width: 210mm; height: 297mm; overflow: hidden; page-break-after: always; }
        .sheet.last { page-break-after: auto; }

        .badge { position: absolute; width: {{ $layout['badge_width'] }}mm; height: {{ $layout['badge_height'] }}mm; overflow: hidden; background: #FFFFFF; }

        /* Badge portrait : l'en-tete, le nom au-dessus de la photo centree,
           l'identifiant dessous, le QR code dans le coin bas droit. Chaque bloc
           centre occupe toute la largeur et centre son texte. */
        .band { position: absolute; top: 0; left: 0; width: {{ $layout['badge_width'] }}mm; height: 22mm; background: #B93A24; }
        .role { position: absolute; top: 2.5mm; left: 0; width: {{ $layout['badge_width'] }}mm; text-align: center; font-size: 22pt; font-weight: 800; letter-spacing: 0.16em; color: #FFFFFF; }
        .edition { position: absolute; top: 15.5mm; left: 0; width: {{ $layout['badge_width'] }}mm; text-align: center; font-size: 8pt; font-weight: 600; color: #FDEBE7; }

        .first-name { position: absolute; top: 25mm; left: 4mm; width: {{ $layout['badge_width'] - 8 }}mm; text-align: center; font-size: 20pt; font-weight: 800; line-height: 1.1; white-space: nowrap; }
        .last-name { position: absolute; top: 34mm; left: 4mm; width: {{ $layout['badge_width'] - 8 }}mm; text-align: center; font-size: 13pt; font-weight: 700; letter-spacing: 0.05em; line-height: 1.1; text-transform: uppercase; white-space: nowrap; color: #443C42; }
        {{-- Un nom long passerait a la ligne, sous la photo : il tient sur
             une ligne en plus petit. --}}
        .first-name.long { font-size: 15pt; }
        .last-name.long { font-size: 10pt; }

        /* La photo en cercle de 60 mm : dompdf decoupe l'image par
           `border-radius`, recadree en carre par BadgePrinter. */
        .photo { position: absolute; top: 41mm; left: {{ ($layout['badge_width'] - 60) / 2 }}mm; width: 60mm; height: 60mm; border-radius: 30mm; }
        {{-- dompdf ne centre pas par `line-height` : les initiales descendent
             par une marge interne, la hauteur totale restant de 60 mm. --}}
        .initials { position: absolute; top: 41mm; left: {{ ($layout['badge_width'] - 60) / 2 }}mm; width: 60mm; height: 38mm; padding-top: 22mm; border-radius: 30mm; background: #FDEBE7; color: #B93A24; font-size: 44pt; font-weight: 800; line-height: 1; text-align: center; }

        .id-label { position: absolute; top: 103mm; left: 0; width: {{ $layout['badge_width'] }}mm; text-align: center; font-size: 6.5pt; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #716B70; }
        .id { position: absolute; top: 106mm; left: 0; width: {{ $layout['badge_width'] }}mm; text-align: center; font-size: 15pt; font-weight: 700; letter-spacing: 0.04em; }

        .qr { position: absolute; top: 111mm; left: {{ $layout['badge_width'] - 27 }}mm; width: 23mm; height: 23mm; }
        .scan { position: absolute; top: 126mm; left: 5mm; width: 50mm; font-size: 6.5pt; line-height: 1.3; color: #716B70; }

        .accent { position: absolute; bottom: 0; left: 0; width: {{ $layout['badge_width'] }}mm; height: 1.2mm; background: #E0533C; }

        .crop { position: absolute; background: #1F1A1C; }
        .guide { position: absolute; background: #D8CFCA; }
    </style>
</head>
<body>
    @foreach ($sheets as $badges)
        <div @class(['sheet', 'last' => $loop->last])>
            @foreach ($badges->values() as $index => $badge)
                @php $cell = $layout['cells'][$index]; @endphp

                <div class="badge" style="left: {{ $cell['left'] }}mm; top: {{ $cell['top'] }}mm;">
                    <div class="band"></div>
                    <div class="role">BÉNÉVOLE</div>
                    <div class="edition">{{ $badge->editionName }}</div>

                    <div @class(['first-name', 'long' => mb_strlen($badge->firstName) > 16])>{{ $badge->firstName }}</div>
                    <div @class(['last-name', 'long' => mb_strlen($badge->lastName) > 16])>{{ $badge->lastName }}</div>

                    @if ($badge->hasPhoto())
                        <img class="photo" src="{{ $badge->photo }}" alt="">
                    @else
                        <div class="initials">{{ $badge->initials }}</div>
                    @endif

                    <div class="id-label">Identifiant</div>
                    <div class="id">{{ $badge->identifier }}</div>

                    <div class="scan">Scannez le code pour vérifier ce badge.</div>
                    <img class="qr" src="{{ $badge->qrCode }}" alt="">

                    <div class="accent"></div>
                </div>
            @endforeach

            {{-- Filets de coupe sur la planche, par-dessus les badges : ils
                 tombent exactement sur leurs bords. --}}
            @php
                $gridLeft = $layout['margin_x'];
                $gridTop = $layout['margin_y'];
                $gridWidth = end($layout['cuts_x']) - $gridLeft;
                $gridHeight = end($layout['cuts_y']) - $gridTop;
            @endphp

            @foreach ($layout['cuts_x'] as $x)
                <div class="guide" style="left: {{ $x - 0.05 }}mm; top: {{ $gridTop }}mm; width: 0.1mm; height: {{ $gridHeight }}mm;"></div>
                <div class="crop" style="left: {{ $x - 0.1 }}mm; top: {{ $gridTop - 7 }}mm; width: 0.2mm; height: 5mm;"></div>
                <div class="crop" style="left: {{ $x - 0.1 }}mm; top: {{ $gridTop + $gridHeight + 2 }}mm; width: 0.2mm; height: 5mm;"></div>
            @endforeach

            @foreach ($layout['cuts_y'] as $y)
                <div class="guide" style="left: {{ $gridLeft }}mm; top: {{ $y - 0.05 }}mm; width: {{ $gridWidth }}mm; height: 0.1mm;"></div>
                <div class="crop" style="left: {{ $gridLeft - 7 }}mm; top: {{ $y - 0.1 }}mm; width: 5mm; height: 0.2mm;"></div>
                <div class="crop" style="left: {{ $gridLeft + $gridWidth + 2 }}mm; top: {{ $y - 0.1 }}mm; width: 5mm; height: 0.2mm;"></div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
