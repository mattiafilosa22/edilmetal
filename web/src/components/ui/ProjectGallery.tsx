"use client";

import Image from "next/image";
import { useState } from "react";
import { useTranslations } from "next-intl";
import type { Image as ProgettoImage } from "@/domain";

type ProjectGalleryProps = { images: ProgettoImage[]; fallbackAlt: string };

const PLACEHOLDER: ProgettoImage = {
  src: "/placeholder-progetto.svg",
  width: 1200,
  height: 800,
  alt: "",
};

/**
 * Galleria della scheda progetto: immagine principale + miniature.
 * La miniatura selezionata (`aria-current`) aggiorna l'immagine grande.
 * Senza foto (realizzazione appena creata in WP) mostra un segnaposto.
 */
export function ProjectGallery({ images, fallbackAlt }: ProjectGalleryProps) {
  const t = useTranslations("Scheda");
  const [active, setActive] = useState(0);
  const main = images[active] ?? images[0] ?? { ...PLACEHOLDER, alt: fallbackAlt };

  return (
    <div className="gallery">
      <div className="gallery__main" aria-label={t("galleryLabel")}>
        <Image src={main.src} alt={main.alt} fill sizes="(max-width: 900px) 100vw, 60vw" priority />
      </div>
      {images.length > 1 ? (
        <div className="gallery__thumbs" role="group" aria-label={t("thumbsLabel")}>
          {images.map((img, i) => (
            <button
              key={`${img.src}-${i}`}
              type="button"
              className="gallery__thumb"
              aria-current={i === active ? "true" : "false"}
              aria-label={img.alt}
              onClick={() => setActive(i)}
            >
              <Image src={img.src} alt="" fill sizes="120px" />
            </button>
          ))}
        </div>
      ) : null}
    </div>
  );
}
