"use client";

import {
  useCallback,
  useEffect,
  useRef,
  useState,
  type ReactNode,
} from "react";
import { useTranslations } from "next-intl";

type RailProps = { children: ReactNode };

const arrow = (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
    <path d="M9 6l6 6-6 6" />
  </svg>
);

/**
 * Rail orizzontale scorrevole (realizzazioni recenti / correlati / officina).
 * Progressive enhancement: scroll nativo su touch/trackpad, drag-to-scroll col
 * puntatore su desktop e frecce prev/next con stato disabilitato agli estremi.
 * Rispetta `prefers-reduced-motion`.
 */
export function Rail({ children }: RailProps) {
  const t = useTranslations("Rail");
  const railRef = useRef<HTMLDivElement>(null);
  const [navHidden, setNavHidden] = useState(true);
  const [atStart, setAtStart] = useState(true);
  const [atEnd, setAtEnd] = useState(false);
  const drag = useRef({ down: false, startX: 0, startScroll: 0, moved: false });

  const update = useCallback(() => {
    const rail = railRef.current;
    if (!rail) return;
    const max = rail.scrollWidth - rail.clientWidth - 1;
    setNavHidden(max <= 0);
    setAtStart(rail.scrollLeft <= 0);
    setAtEnd(rail.scrollLeft >= max);
  }, []);

  useEffect(() => {
    update();
    window.addEventListener("resize", update);
    return () => window.removeEventListener("resize", update);
  }, [update]);

  const step = useCallback((dir: 1 | -1) => {
    const rail = railRef.current;
    if (!rail) return;
    const card = rail.querySelector<HTMLElement>(".proj");
    const amount = card ? card.getBoundingClientRect().width + 24 : rail.clientWidth * 0.85;
    const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    rail.scrollBy({ left: amount * dir, behavior: reduce ? "auto" : "smooth" });
  }, []);

  const onPointerDown = useCallback((e: React.PointerEvent<HTMLDivElement>) => {
    if (e.pointerType === "touch") return;
    const rail = railRef.current;
    if (!rail) return;
    drag.current = { down: true, startX: e.clientX, startScroll: rail.scrollLeft, moved: false };
  }, []);

  useEffect(() => {
    function onMove(e: PointerEvent) {
      const rail = railRef.current;
      if (!rail || !drag.current.down) return;
      const dx = e.clientX - drag.current.startX;
      // Solo a trascinamento confermato attivo `is-grabbing` (e quindi
      // `pointer-events:none` sulle card): se scatta già al pointerdown, un
      // semplice click perde il target al mouseup e il link non naviga mai.
      if (!drag.current.moved && Math.abs(dx) > 10) {
        drag.current.moved = true;
        rail.classList.add("is-grabbing");
      }
      rail.scrollLeft = drag.current.startScroll - dx;
    }
    function onUp() {
      drag.current.down = false;
      railRef.current?.classList.remove("is-grabbing");
    }
    window.addEventListener("pointermove", onMove);
    window.addEventListener("pointerup", onUp);
    return () => {
      window.removeEventListener("pointermove", onMove);
      window.removeEventListener("pointerup", onUp);
    };
  }, []);

  // Se ho trascinato, non seguo il link della card.
  const onClickCapture = useCallback((e: React.MouseEvent<HTMLDivElement>) => {
    if (drag.current.moved) {
      e.preventDefault();
      e.stopPropagation();
      drag.current.moved = false;
    }
  }, []);

  return (
    <>
      <div
        className="rail"
        ref={railRef}
        onScroll={update}
        onPointerDown={onPointerDown}
        onClickCapture={onClickCapture}
      >
        {children}
      </div>
      <div className="rail-nav" hidden={navHidden}>
        <button
          type="button"
          className="rail-arrow rail-arrow--prev"
          aria-label={t("prev")}
          disabled={atStart}
          onClick={() => step(-1)}
        >
          {arrow}
        </button>
        <button
          type="button"
          className="rail-arrow"
          aria-label={t("next")}
          disabled={atEnd}
          onClick={() => step(1)}
        >
          {arrow}
        </button>
      </div>
    </>
  );
}
