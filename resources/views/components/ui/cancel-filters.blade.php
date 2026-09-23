{{-- SVG filters for x-ui.cancel-btn (include once per page) --}}
<svg class="cancel-btn-filters" aria-hidden="true" focusable="false">
    <filter id="cancel-bump">
        <feTurbulence result="noise" numOctaves="4" baseFrequency="0.678" type="fractalNoise"></feTurbulence>
        <feSpecularLighting result="specular" lighting-color="#fffffa" specularExponent="15" specularConstant="0.7" surfaceScale="0.22" in="noise">
            <fePointLight z="210" y="-50" x="40"></fePointLight>
        </feSpecularLighting>
        <feComposite result="noise2" operator="in" in="specular" in2="SourceGraphic"></feComposite>
        <feBlend mode="difference" in2="noise2" in="SourceGraphic" result="out"></feBlend>
        <feBlend mode="overlay" in2="out" in="SourceGraphic"></feBlend>
    </filter>
    <defs>
        <filter id="cancel-linen">
            <feTurbulence type="fractalNoise" baseFrequency="0.9 0.03" numOctaves="2" seed="8" result="verticalNoise"></feTurbulence>
            <feTurbulence type="fractalNoise" baseFrequency="0.03 0.9" numOctaves="2" seed="12" result="horizontalNoise"></feTurbulence>
            <feBlend in="verticalNoise" in2="horizontalNoise" mode="multiply" result="woven"></feBlend>
            <feComponentTransfer in="woven" result="threadContrast">
                <feFuncR type="gamma" amplitude="1.3" exponent="2.4"></feFuncR>
                <feFuncG type="gamma" amplitude="1.3" exponent="2.4"></feFuncG>
                <feFuncB type="gamma" amplitude="1.3" exponent="2.4"></feFuncB>
            </feComponentTransfer>
            <feGaussianBlur in="threadContrast" stdDeviation="0.22" result="softThreads"></feGaussianBlur>
            <feComposite in="softThreads" in2="SourceGraphic" operator="in" result="textureMask"></feComposite>
            <feBlend in="SourceGraphic" in2="textureMask" mode="color-burn"></feBlend>
        </filter>
    </defs>
</svg>
