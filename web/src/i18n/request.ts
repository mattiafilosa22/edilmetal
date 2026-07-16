import { getRequestConfig } from "next-intl/server";
import { hasLocale } from "next-intl";
import { routing } from "./routing";

/**
 * Carica i messaggi UI per la locale richiesta (segmento `[locale]`).
 * Con lo static export non esiste middleware: la locale arriva dai
 * parametri di route e viene validata contro `routing.locales`.
 */
export default getRequestConfig(async ({ requestLocale }) => {
  const requested = await requestLocale;
  const locale = hasLocale(routing.locales, requested)
    ? requested
    : routing.defaultLocale;

  return {
    locale,
    messages: (await import(`./messages/${locale}.json`)).default,
  };
});
