"use client";

import {
  useCallback,
  useEffect,
  useState,
  useSyncExternalStore,
  type CSSProperties,
} from "react";
import Image from "next/image";
import { useTranslations } from "next-intl";
import type { Image as ImageDto } from "@/domain";

type HeroSliderProps = {
  /** Foto a piena larghezza, in ordine di apparizione (almeno una). */
  slides: ImageDto[];
  /** Durata di ogni slide in millisecondi. */
  intervalMs?: number;
};

const DEFAULT_INTERVAL_MS = 6000;

const chevron = (
  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
    <path d="M9 6l6 6-6 6" />
  </svg>
);

/* Preferenze e stato dell'ambiente letti come "store esterni": niente stato
   duplicato in React, nessun effetto che scrive stato al mount. */

const REDUCE_MOTION = "(prefers-reduced-motion: reduce)";

function subscribeReduceMotion(onChange: () => void): () => void {
  const query = window.matchMedia?.(REDUCE_MOTION);
  query?.addEventListener("change", onChange);
  return () => query?.removeEventListener("change", onChange);
}

function getReduceMotion(): boolean {
  return window.matchMedia?.(REDUCE_MOTION).matches ?? false;
}

function subscribeVisibility(onChange: () => void): () => void {
  document.addEventListener("visibilitychange", onChange);
  return () => document.removeEventListener("visibilitychange", onChange);
}

function getDocumentHidden(): boolean {
  return document.hidden;
}

/** In SSR non c'è animazione da riprodurre: nessun autoplay, scheda "nascosta". */
const serverTrue = () => true;

/** "1" → "01": numerazione mono da disegno tecnico. */
function pad(n: number): string {
  return String(n).padStart(2, "0");
}

/**
 * Slider dell'hero della home: crossfade a piena larghezza con avanzamento
 * automatico, frecce, puntini e contatore mono.
 *
 * Progressive enhancement: la prima foto è nel markup con `priority` (LCP), le
 * altre si sovrappongono in dissolvenza. L'autoplay si mette in pausa su hover,
 * al focus da tastiera e quando la scheda non è visibile; con
 * `prefers-reduced-motion` non parte affatto e restano i soli comandi manuali.
 */
export function HeroSlider({ slides, intervalMs = DEFAULT_INTERVAL_MS }: HeroSliderProps) {
  const t = useTranslations("HeroSlider");
  const [index, setIndex] = useState(0);
  const [hovered, setHovered] = useState(false);
  const total = slides.length;

  // In SSR/export statico l'autoplay è spento: parte all'idratazione, e solo se
  // l'utente non ha chiesto meno animazioni. La prima foto resta sempre visibile.
  const reduceMotion = useSyncExternalStore(
    subscribeReduceMotion,
    getReduceMotion,
    serverTrue
  );
  const hidden = useSyncExternalStore(
    subscribeVisibility,
    getDocumentHidden,
    serverTrue
  );
  const running = !reduceMotion && !hovered && !hidden && total > 1;

  const goTo = useCallback(
    (next: number) => setIndex(((next % total) + total) % total),
    [total]
  );

  useEffect(() => {
    if (!running) return;
    const id = window.setInterval(() => goTo(index + 1), intervalMs);
    return () => window.clearInterval(id);
  }, [running, index, intervalMs, goTo]);

  if (total === 0) return null;

  return (
    <div
      className="hero-slider"
      role="group"
      aria-roledescription="carousel"
      aria-label={t("label")}
      onMouseEnter={() => setHovered(true)}
      onMouseLeave={() => setHovered(false)}
      onFocusCapture={() => setHovered(true)}
      onBlurCapture={() => setHovered(false)}
      style={{ "--hero-slider-duration": `${intervalMs}ms` } as CSSProperties}
    >
      <div className="hero-slider__stage">
        {slides.map((slide, i) => (
          <div
            key={slide.src}
            className="hero-slider__slide"
            data-active={i === index ? "" : undefined}
            aria-hidden={i !== index}
            inert={i !== index ? true : undefined}
          >
            <Image
              src={slide.src}
              alt={slide.alt}
              width={slide.width}
              height={slide.height}
              sizes="100vw"
              priority={i === 0}
              loading={i === 0 ? undefined : "lazy"}
            />
          </div>
        ))}
        <div className="hero-slider__scrim" aria-hidden="true" />
      </div>

      {total > 1 ? (
        <>
          <button
            type="button"
            className="hero-slider__arrow hero-slider__arrow--prev"
            aria-label={t("prev")}
            onClick={() => goTo(index - 1)}
          >
            {chevron}
          </button>
          <button
            type="button"
            className="hero-slider__arrow hero-slider__arrow--next"
            aria-label={t("next")}
            onClick={() => goTo(index + 1)}
          >
            {chevron}
          </button>

          <div className="hero-slider__hud">
            <span className="hero-slider__counter" aria-hidden="true">
              {pad(index + 1)} <i>/</i> {pad(total)}
            </span>
            <div className="hero-slider__dots">
              {slides.map((slide, i) => (
                <button
                  key={slide.src}
                  type="button"
                  className="hero-slider__dot"
                  aria-label={t("goTo", { n: i + 1 })}
                  aria-current={i === index ? "true" : undefined}
                  onClick={() => goTo(i)}
                >
                  <span
                    className="hero-slider__dot-fill"
                    data-running={i === index && running ? "" : undefined}
                  />
                </button>
              ))}
            </div>
          </div>

          <div className="sr-only" aria-live="polite">
            {t("status", { current: index + 1, total })}
          </div>
        </>
      ) : null}
    </div>
  );
}
