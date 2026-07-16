export {
  SITE_URL,
  absoluteUrl,
  localePath,
  progettoPath,
  localePathsFor,
  OG_LOCALE,
} from "./site";
export {
  buildMetadata,
  buildLanguageAlternates,
  DEFAULT_OG_IMAGE,
  type SeoInput,
  type SeoImage,
} from "./metadata";
export {
  pruneJson,
  schemaNode,
  type JsonValue,
  type JsonObject,
  type JsonInput,
} from "./jsonLd";
export {
  parseAddress,
  buildOpeningHours,
  socialSameAs,
  buildProjectJsonLd,
  buildOrganizationJsonLd,
  buildBreadcrumbJsonLd,
  type Crumb,
  type ProjectJsonLdInput,
  type OrganizationJsonLdInput,
} from "./structured";
export { JsonLd } from "./JsonLdScript";
