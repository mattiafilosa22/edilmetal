"use client";

import {
  useEffect,
  useRef,
  type CSSProperties,
  type ElementType,
  type ReactNode,
} from "react";

type RevealProps = {
  /** Elemento DOM da rendere (default `div`). */
  as?: ElementType;
  className?: string;
  /** Posizione nella sequenza: introduce un piccolo ritardo a cascata. */
  index?: number;
  children: ReactNode;
};

/**
 * Rivela il contenuto con una transizione all'ingresso nel viewport.
 * La classe `.reveal` (che nasconde inizialmente l'elemento) viene aggiunta
 * SOLO via JS: senza JavaScript, o con `prefers-reduced-motion`, il contenuto
 * resta immediatamente visibile — nessun impatto su export statico e SEO.
 */
export function Reveal({ as: Tag = "div", className, index = 0, children }: RevealProps) {
  const ref = useRef<HTMLElement>(null);

  useEffect(() => {
    const el = ref.current;
    if (!el || !("IntersectionObserver" in window)) return;
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;

    el.classList.add("reveal");
    const observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        }
      },
      { threshold: 0.12, rootMargin: "0px 0px -40px 0px" }
    );
    observer.observe(el);
    return () => observer.disconnect();
  }, []);

  const style = { "--rd": `${Math.min(index, 6) * 70}ms` } as CSSProperties;
  return (
    <Tag ref={ref} className={className} style={style}>
      {children}
    </Tag>
  );
}
