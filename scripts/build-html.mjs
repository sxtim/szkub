import { promises as fs } from "fs";
import path from "path";

const SRC_ROOT = path.resolve("src/html");
const DIST_ROOT = path.resolve("dist");
const INCLUDE_RE = /@@include\(\s*['"]([^'"]+)['"]\s*\)/g;

const readFile = (filePath) => fs.readFile(filePath, "utf8");

const expandIncludes = async (content, baseDir, stack = []) => {
  let result = "";
  let lastIndex = 0;

  for (const match of content.matchAll(INCLUDE_RE)) {
    const includePath = path.resolve(baseDir, match[1]);

    if (stack.includes(includePath)) {
      const chain = [...stack, includePath].map((item) => path.relative(SRC_ROOT, item));
      throw new Error(`Circular include detected: ${chain.join(" -> ")}`);
    }

    const includeContent = await readFile(includePath);
    const expanded = await expandIncludes(
      includeContent,
      path.dirname(includePath),
      [...stack, includePath]
    );

    result += content.slice(lastIndex, match.index);
    result += expanded;
    lastIndex = match.index + match[0].length;
  }

  result += content.slice(lastIndex);
  return result;
};

const walkHtml = async (dir) => {
  const entries = await fs.readdir(dir, { withFileTypes: true });
  const files = [];

  for (const entry of entries) {
    const entryPath = path.join(dir, entry.name);

    if (entry.isDirectory()) {
      if (entry.name === "blocks") {
        continue;
      }
      files.push(...(await walkHtml(entryPath)));
      continue;
    }

    if (entry.isFile() && entry.name.endsWith(".html")) {
      files.push(entryPath);
    }
  }

  return files;
};

const buildHtml = async () => {
  const htmlFiles = await walkHtml(SRC_ROOT);

  for (const filePath of htmlFiles) {
    const content = await readFile(filePath);
    const expanded = await expandIncludes(content, path.dirname(filePath));
    const relativePath = path.relative(SRC_ROOT, filePath);
    const outputPath = path.join(DIST_ROOT, relativePath);

    await fs.mkdir(path.dirname(outputPath), { recursive: true });
    await fs.writeFile(outputPath, expanded, "utf8");
  }
};

buildHtml().catch((error) => {
  console.error(error);
  process.exit(1);
});
