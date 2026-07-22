import Image from "next/image";
import Link from "next/link";
import { useTranslations } from "next-intl";
import type { HomeFeatured } from "@/domain";
import type { Locale } from "@/i18n/routing";

type FeaturedCategoriesProps = {
  items: HomeFeatured[];
  locale: Locale;
};

/**
 * Blocco "In evidenza": fino a 2 categorie con foto reale, come nel sito
 * storico. Ogni card rimanda alla lista realizzazioni pre-filtrata.
 */
export function FeaturedCategories({ items, locale }: FeaturedCategoriesProps) {
  const t = useTranslations("Home");

  if (items.length === 0) {
    return null;
  }

  return (
    <div className="featured">
      {items.map((item) => (
        <Link
          key={item.categoria.slug}
          className="featured__card"
          href={`/${locale}/realizzazioni?categoria=${item.categoria.slug}`}
        >
          <Image
            src={item.immagine.src}
            alt={item.immagine.alt}
            fill
            sizes="(max-width: 720px) 100vw, 50vw"
          />
          <div className="featured__overlay">
            <h3>{item.categoria.nome}</h3>
            <span className="featured__cta">{t("categorieCtaLabel")}</span>
          </div>
        </Link>
      ))}
    </div>
  );
}
