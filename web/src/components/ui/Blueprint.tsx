type BlueprintProps = {
  /** Etichetta accessibile dello schema tecnico. */
  ariaLabel: string;
  /** Mostra quote e testi dimensionali (hero home). */
  withDims?: boolean;
};

/**
 * Disegno tecnico "blueprint" di un telaio in acciaio con capriata, con
 * animazione line-draw (disattivata da `prefers-reduced-motion` via CSS).
 * Decorativo ma con `role="img"` e label per gli screen reader.
 */
export function Blueprint({ ariaLabel, withDims = false }: BlueprintProps) {
  return (
    <svg className="blueprint" viewBox="0 0 420 420" role="img" aria-label={ariaLabel}>
      <path className="draw" d="M80 360 L80 150 M340 360 L340 150" />
      <path className="draw" d="M80 150 L340 150" />
      <path className="draw" d="M80 150 L210 92 L340 150" />
      <path className="draw" d="M80 150 L145 121 L210 92 L275 121 L340 150" />
      <path className="draw" d="M145 150 L145 121 M210 150 L210 92 M275 150 L275 121" />
      <path className="draw" d="M40 360 L380 360" />
      <circle className="node" cx="80" cy="150" r="4" />
      <circle className="node" cx="340" cy="150" r="4" />
      <circle className="node" cx="210" cy="92" r="4.5" />
      <circle className="node" cx="145" cy="121" r="3.5" />
      <circle className="node" cx="275" cy="121" r="3.5" />
      <circle className="node" cx="80" cy="360" r="4" />
      <circle className="node" cx="340" cy="360" r="4" />
      {withDims ? (
        <>
          <path className="dim" d="M80 388 L340 388 M80 382 L80 394 M340 382 L340 394" />
          <text className="dimtxt" x="210" y="408" textAnchor="middle">
            L = 24,00 m
          </text>
          <path className="dim" d="M54 150 L54 360 M48 150 L60 150 M48 360 L60 360" />
          <text
            className="dimtxt"
            x="28"
            y="255"
            textAnchor="middle"
            transform="rotate(-90 28 255)"
          >
            H = 8,00
          </text>
        </>
      ) : null}
    </svg>
  );
}
