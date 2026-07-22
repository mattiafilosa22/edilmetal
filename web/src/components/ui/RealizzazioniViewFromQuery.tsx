"use client";

import { useSearchParams } from "next/navigation";
import { categoriaSlugSchema, type Categoria, type ProgettoSummary } from "@/domain";
import type { Locale } from "@/i18n/routing";
import { RealizzazioniView } from "./RealizzazioniView";

type RealizzazioniViewFromQueryProps = {
  summaries: ProgettoSummary[];
  categorie: Categoria[];
  settori: string[];
  anni: number[];
  locale: Locale;
};

/**
 * Adatta `RealizzazioniView` all'export statico: non esiste un server in
 * grado di leggere `?categoria=` a request-time, quindi la query string va
 * letta lato client (`useSearchParams`, valida solo dopo l'idratazione) e
 * validata con lo schema di dominio prima di pre-selezionare la categoria.
 * Il chiamante deve avvolgere questo componente in `<Suspense>`, come
 * richiesto da Next.js per `useSearchParams` in un export statico.
 */
export function RealizzazioniViewFromQuery(props: RealizzazioniViewFromQueryProps) {
  const searchParams = useSearchParams();
  const parsedCategoria = categoriaSlugSchema.safeParse(searchParams.get("categoria") ?? undefined);
  const initialCategoria = parsedCategoria.success ? parsedCategoria.data : undefined;

  return <RealizzazioniView {...props} initialCategoria={initialCategoria} />;
}
