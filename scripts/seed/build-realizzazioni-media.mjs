// scripts/seed/build-realizzazioni-media.mjs
// One-off data-prep script: groups the historic photo dump into per-client
// project folders, copies the files into the committed seed-media tree, and
// emits the manifest consumed by Catalog::historic_progetti().
import { readdirSync, mkdirSync, copyFileSync, writeFileSync, statSync } from "node:fs";
import { join, extname, basename } from "node:path";

const SOURCE_ROOT = "sito web edilmetal 2018";
const MEDIA_ROOT = "cms/seed/media/realizzazioni";
const MANIFEST_PATH = "cms/mu-plugins/edilmetal-core/seed/Data/realizzazioni-storiche.json";

/** Cartella sorgente => slug categoria_opera. */
const CATEGORY_FOLDERS = {
  "1 strutture acciaio": "strutture-acciaio",
  "2 strutture miste": "strutture-miste",
  "3 scale": "scale",
  "4 pensiline": "pensiline",
  "5 pensiline auto": "pensiline-auto",
  "6 coperture e tamponamenti": "coperture-tamponamenti",
  "7 rivestimenti facciata": "rivestimenti-facciata",
  "8 opere speciali": "opere-speciali",
};

/** Nomi-base da scartare: privi di cliente riconoscibile. */
const EXCLUDE_BASENAMES = new Set(["p17-10-08_11."]);

/** Correzioni manuali: nome-base grezzo (minuscolo) => nome-base corretto. */
const BASENAME_OVERRIDES = new Map([
  ["ricci casa-apr07 002-", "ricci casa-apr07"],
]);

/** Acronimi da mantenere maiuscoli nel titolo (dopo Title Case). */
const ACRONYMS = new Map([
  ["Cna", "CNA"],
  ["Sit", "SIT"],
  ["Fbp", "FBP"],
  ["Zrh", "ZRH"],
  ["Ct", "CT"],
  ["Cls", "CLS"],
]);

function titleCase(raw) {
  const words = raw
    .trim()
    .split(/\s+/)
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase());
  return words.map((w) => ACRONYMS.get(w) ?? w).join(" ");
}

function slugify(raw) {
  return raw
    .toLowerCase()
    .normalize("NFD")
    .replace(/[̀-ͯ]/g, "")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

function groupPhotos(folderPath) {
  const groups = new Map(); // basename (lowercase, corrected) -> file[]
  for (const file of readdirSync(folderPath).sort()) {
    if (!/\.(jpe?g)$/i.test(file)) continue;
    if (!statSync(join(folderPath, file)).isFile()) continue;

    const stem = file.replace(/\.(jpe?g)$/i, "");
    let base = stem.replace(/\s*\d+\s*$/, "").trim().replace(/\s+/g, " ");
    const key = base.toLowerCase();

    if (EXCLUDE_BASENAMES.has(key)) continue;

    const corrected = BASENAME_OVERRIDES.get(key) ?? base;
    const groupKey = corrected.toLowerCase();

    if (!groups.has(groupKey)) groups.set(groupKey, { title: corrected, files: [] });
    groups.get(groupKey).files.push(file);
  }
  return groups;
}

const manifest = [];

for (const [folderName, categoriaSlug] of Object.entries(CATEGORY_FOLDERS)) {
  const folderPath = join(SOURCE_ROOT, folderName);
  const groups = groupPhotos(folderPath);

  for (const { title, files } of groups.values()) {
    const clientSlug = slugify(title);
    const ref = `${categoriaSlug}-${clientSlug}`;
    const destDir = join(MEDIA_ROOT, categoriaSlug, ref);
    mkdirSync(destDir, { recursive: true });

    const media = files.map((file, index) => {
      const nn = String(index + 1).padStart(2, "0");
      const destFile = join(destDir, `${nn}.jpg`);
      copyFileSync(join(folderPath, file), destFile);
      return `real:${categoriaSlug}/${ref}/${nn}`;
    });

    manifest.push({
      ref,
      titolo: titleCase(title),
      categoria: categoriaSlug,
      media,
    });
  }
}

writeFileSync(MANIFEST_PATH, JSON.stringify(manifest, null, 2) + "\n", "utf8");
console.log(`Wrote ${manifest.length} historic progetti to ${MANIFEST_PATH}`);
console.log(`Copied photos into ${MEDIA_ROOT}/`);
