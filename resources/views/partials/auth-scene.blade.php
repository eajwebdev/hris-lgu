{{--
    The landscape at the foot of the sign-in panel, drawn from what is on the
    municipal seal: pines on a ridge, green hills, the river at the bottom, and
    the seal's orange as the sun. Sits behind the panel's text; the parent is
    expected to be `relative isolate overflow-hidden`.
--}}
<svg class="pointer-events-none absolute inset-x-0 bottom-0 -z-10 h-32 w-full lg:h-[44%] lg:max-h-[26rem]"
     viewBox="0 0 800 360" preserveAspectRatio="xMidYMax slice" aria-hidden="true" focusable="false">
    <defs>
        <symbol id="sc-pine" viewBox="0 0 48 84">
            <path d="M24 0 35 20h-6l11 22h-7l13 24H28v18h-8V66H2l13-24H8l11-22h-6z"/>
        </symbol>
    </defs>

    <g class="motion-safe:animate-sunrise">
        <circle class="fill-none stroke-sun-500/15 stroke-[1.5]" cx="590" cy="170" r="150"/>
        <circle class="fill-none stroke-sun-500/30 stroke-[1.5]" cx="590" cy="170" r="114"/>
        <circle class="fill-sun-500" cx="590" cy="170" r="78"/>
    </g>

    <path class="fill-forest-800" d="M0 220C90 170 170 150 260 190c80 35 140-40 240-30s180 55 300 30v170H0z"/>
    <g class="fill-forest-950">
        <use href="#sc-pine" x="104" y="122" width="32" height="56"/>
        <use href="#sc-pine" x="128" y="103" width="41" height="72"/>
        <use href="#sc-pine" x="161" y="115" width="34" height="60"/>
        <use href="#sc-pine" x="180" y="97"  width="46" height="80"/>
        <use href="#sc-pine" x="218" y="127" width="33" height="58"/>
        <use href="#sc-pine" x="242" y="129" width="38" height="66"/>
    </g>

    <path class="fill-forest-700" d="M0 270c120-55 230-40 340-8s220-47 340-27c60 10 100 27 120 35v90H0z"/>
    <g class="fill-forest-950">
        <use href="#sc-pine" x="587" y="142" width="55" height="96"/>
        <use href="#sc-pine" x="617" y="119" width="69" height="120"/>
        <use href="#sc-pine" x="663" y="143" width="57" height="100"/>
        <use href="#sc-pine" x="700" y="166" width="48" height="84"/>
    </g>

    <path class="fill-forest-600" d="M0 318c140-38 300-26 430-2s250-16 370-6v50H0z"/>
    <path class="fill-none stroke-cream stroke-[7] [stroke-linecap:round]" d="M-10 336c100-16 180 10 280-4s180-12 270 4 180-10 270 2"/>
    <path class="fill-none stroke-cream/50 stroke-[3] [stroke-linecap:round]" d="M-10 349c100-14 180 8 280-2s180-10 270 2 180-8 270 0"/>
</svg>
