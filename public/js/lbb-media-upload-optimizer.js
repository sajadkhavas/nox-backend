const MEDIA_LIBRARY_PATH = "/admin/storefront-media-assets";
const TARGET_BYTES = 1024 * 1024;
const MAX_LONG_EDGE = 2200;
const MIN_PHOTO_QUALITY = 0.72;
const SUPPORTED_TYPES = new Set(["image/jpeg", "image/png", "image/webp"]);

let activeDialog = null;

const formatBytes = (bytes) => {
  if (!Number.isFinite(bytes) || bytes < 0) return "—";
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
};

const replaceExtension = (name, extension) => {
  const stem = name.replace(/\.[^.]+$/, "") || "lbb-image";
  return `${stem}.${extension}`;
};

const loadBitmap = async (file) => {
  if ("createImageBitmap" in window) {
    try {
      return await createImageBitmap(file, { imageOrientation: "from-image" });
    } catch (_) {
      // Fall through to the HTMLImageElement decoder.
    }
  }

  const objectUrl = URL.createObjectURL(file);
  try {
    const image = new Image();
    image.decoding = "async";
    image.src = objectUrl;
    await image.decode();
    return image;
  } finally {
    URL.revokeObjectURL(objectUrl);
  }
};

const dimensionsOf = (bitmap) => ({
  width: bitmap.width || bitmap.naturalWidth || 0,
  height: bitmap.height || bitmap.naturalHeight || 0,
});

const scaledDimensions = (width, height, longEdge) => {
  const longest = Math.max(width, height);
  if (!longest || longest <= longEdge) return { width, height };

  const ratio = longEdge / longest;
  return {
    width: Math.max(1, Math.round(width * ratio)),
    height: Math.max(1, Math.round(height * ratio)),
  };
};

const canvasBlob = (bitmap, width, height, type, quality) =>
  new Promise((resolve, reject) => {
    const canvas = document.createElement("canvas");
    canvas.width = width;
    canvas.height = height;

    const context = canvas.getContext("2d", { alpha: type === "image/png" });
    if (!context) {
      reject(new Error("مرورگر امکان پردازش تصویر را فراهم نکرد."));
      return;
    }

    context.imageSmoothingEnabled = true;
    context.imageSmoothingQuality = "high";
    context.drawImage(bitmap, 0, 0, width, height);
    canvas.toBlob(
      (blob) => {
        if (!blob) {
          reject(new Error("ساخت نسخه بهینه ناموفق بود."));
          return;
        }
        resolve(blob);
      },
      type,
      quality,
    );
  });

const optimizePhoto = async (bitmap, source) => {
  const sourceDimensions = dimensionsOf(bitmap);
  let { width, height } = scaledDimensions(
    sourceDimensions.width,
    sourceDimensions.height,
    MAX_LONG_EDGE,
  );
  let quality = 0.88;
  let best = null;

  for (let attempt = 0; attempt < 10; attempt += 1) {
    const blob = await canvasBlob(bitmap, width, height, "image/webp", quality);
    if (!best || blob.size < best.blob.size) {
      best = { blob, width, height, type: "image/webp" };
    }
    if (blob.size <= TARGET_BYTES) break;

    if (quality > MIN_PHOTO_QUALITY) {
      quality = Math.max(MIN_PHOTO_QUALITY, quality - 0.04);
    } else {
      width = Math.max(640, Math.round(width * 0.86));
      height = Math.max(640, Math.round(height * 0.86));
    }
  }

  const outputName = replaceExtension(source.name, "webp");
  return {
    file: new File([best.blob], outputName, {
      type: best.type,
      lastModified: Date.now(),
    }),
    width: best.width,
    height: best.height,
    targetReached: best.blob.size <= TARGET_BYTES,
  };
};

const optimizePng = async (bitmap, source) => {
  const sourceDimensions = dimensionsOf(bitmap);
  let { width, height } = scaledDimensions(
    sourceDimensions.width,
    sourceDimensions.height,
    MAX_LONG_EDGE,
  );
  let best = null;

  for (let attempt = 0; attempt < 9; attempt += 1) {
    const blob = await canvasBlob(bitmap, width, height, "image/png");
    if (!best || blob.size < best.blob.size) {
      best = { blob, width, height, type: "image/png" };
    }
    if (blob.size <= TARGET_BYTES) break;

    width = Math.max(640, Math.round(width * 0.84));
    height = Math.max(640, Math.round(height * 0.84));
  }

  return {
    file: new File([best.blob], replaceExtension(source.name, "png"), {
      type: "image/png",
      lastModified: Date.now(),
    }),
    width: best.width,
    height: best.height,
    targetReached: best.blob.size <= TARGET_BYTES,
  };
};

const optimizeFile = async (file) => {
  const bitmap = await loadBitmap(file);
  try {
    const dimensions = dimensionsOf(bitmap);
    if (!dimensions.width || !dimensions.height) {
      throw new Error("ابعاد تصویر قابل تشخیص نیست.");
    }

    if (file.type === "image/png") {
      return { ...await optimizePng(bitmap, file), sourceDimensions: dimensions };
    }

    return { ...await optimizePhoto(bitmap, file), sourceDimensions: dimensions };
  } finally {
    if (typeof bitmap.close === "function") bitmap.close();
  }
};

const commitFileToInput = (input, file) => {
  const transfer = new DataTransfer();
  transfer.items.add(file);
  input.files = transfer.files;
  input.dataset.lbbOptimizerBypass = "1";
  input.dispatchEvent(new Event("change", { bubbles: true }));
};

const clearInput = (input) => {
  try {
    input.value = "";
  } catch (_) {
    // Some browser/file-input combinations do not allow programmatic assignment.
  }
};

const createDialog = ({ input, sourceFile }) => {
  activeDialog?.remove();

  const backdrop = document.createElement("div");
  backdrop.setAttribute("role", "presentation");
  backdrop.style.cssText = [
    "position:fixed",
    "inset:0",
    "z-index:2147483000",
    "background:rgba(15,23,42,.64)",
    "display:flex",
    "align-items:center",
    "justify-content:center",
    "padding:16px",
    "direction:rtl",
  ].join(";");

  const dialog = document.createElement("section");
  dialog.setAttribute("role", "dialog");
  dialog.setAttribute("aria-modal", "true");
  dialog.setAttribute("aria-labelledby", "lbb-media-optimizer-title");
  dialog.style.cssText = [
    "width:min(560px,100%)",
    "max-height:min(760px,92vh)",
    "overflow:auto",
    "background:#fff",
    "color:#111827",
    "border-radius:20px",
    "box-shadow:0 24px 80px rgba(0,0,0,.28)",
    "padding:22px",
    "font-family:Vazirmatn,system-ui,sans-serif",
  ].join(";");

  dialog.innerHTML = `
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px">
      <div>
        <h2 id="lbb-media-optimizer-title" style="font-size:18px;font-weight:900;margin:0">آماده‌سازی تصویر برای آپلود</h2>
        <p style="font-size:13px;line-height:1.9;color:#6b7280;margin:6px 0 0">فایل هنوز برای سرور ارسال نشده است. می‌توانید همان فایل را آپلود کنید یا ابتدا یک نسخه سبک‌تر برای وب بسازید.</p>
      </div>
      <button type="button" data-action="cancel" aria-label="بستن" style="border:0;background:#f3f4f6;border-radius:10px;width:38px;height:38px;font-size:22px;cursor:pointer">×</button>
    </div>
    <div data-role="source" style="margin-top:18px;border:1px solid #e5e7eb;border-radius:14px;padding:14px;font-size:13px;line-height:2"></div>
    <div data-role="status" aria-live="polite" style="display:none;margin-top:12px;border-radius:12px;padding:12px;font-size:13px;line-height:1.9;background:#f0fdf4;color:#166534"></div>
    <div data-role="error" role="alert" style="display:none;margin-top:12px;border-radius:12px;padding:12px;font-size:13px;line-height:1.9;background:#fef2f2;color:#991b1b"></div>
    <div data-role="actions" style="display:grid;grid-template-columns:1fr;gap:10px;margin-top:18px">
      <button type="button" data-action="optimize" style="min-height:46px;border:0;border-radius:12px;background:#d0e596;color:#1f2937;font-weight:900;cursor:pointer">بهینه‌سازی برای وب</button>
      <button type="button" data-action="original" style="min-height:46px;border:1px solid #d1d5db;border-radius:12px;background:#fff;color:#111827;font-weight:800;cursor:pointer">آپلود فایل اصلی</button>
      <button type="button" data-action="use-optimized" style="display:none;min-height:46px;border:0;border-radius:12px;background:#166534;color:#fff;font-weight:900;cursor:pointer">استفاده از نسخه بهینه</button>
      <button type="button" data-action="back-original" style="display:none;min-height:46px;border:1px solid #d1d5db;border-radius:12px;background:#fff;color:#111827;font-weight:800;cursor:pointer">بازگشت به فایل اصلی</button>
    </div>
  `;

  backdrop.append(dialog);
  document.body.append(backdrop);
  activeDialog = backdrop;

  const sourceBox = dialog.querySelector('[data-role="source"]');
  const statusBox = dialog.querySelector('[data-role="status"]');
  const errorBox = dialog.querySelector('[data-role="error"]');
  const optimizeButton = dialog.querySelector('[data-action="optimize"]');
  const originalButton = dialog.querySelector('[data-action="original"]');
  const useOptimizedButton = dialog.querySelector('[data-action="use-optimized"]');
  const backOriginalButton = dialog.querySelector('[data-action="back-original"]');
  const cancelButton = dialog.querySelector('[data-action="cancel"]');

  sourceBox.textContent = `${sourceFile.name} · ${sourceFile.type || "فرمت نامشخص"} · ${formatBytes(sourceFile.size)}`;

  let optimized = null;
  let sourceDimensions = null;

  const close = ({ clear = false } = {}) => {
    if (clear) clearInput(input);
    backdrop.remove();
    if (activeDialog === backdrop) activeDialog = null;
  };

  cancelButton.addEventListener("click", () => close({ clear: true }));
  backdrop.addEventListener("click", (event) => {
    if (event.target === backdrop) close({ clear: true });
  });

  originalButton.addEventListener("click", () => {
    close();
    commitFileToInput(input, sourceFile);
  });

  backOriginalButton.addEventListener("click", () => {
    close();
    commitFileToInput(input, sourceFile);
  });

  optimizeButton.addEventListener("click", async () => {
    optimizeButton.disabled = true;
    optimizeButton.textContent = "در حال بهینه‌سازی…";
    errorBox.style.display = "none";

    try {
      optimized = await optimizeFile(sourceFile);
      sourceDimensions = optimized.sourceDimensions;
      statusBox.style.display = "block";
      statusBox.textContent = [
        `${formatBytes(sourceFile.size)} → ${formatBytes(optimized.file.size)}`,
        `${sourceDimensions.width}×${sourceDimensions.height} → ${optimized.width}×${optimized.height}`,
        optimized.targetReached
          ? "هدف حدود ۱MB با موفقیت رعایت شد."
          : "برای جلوگیری از افت محسوس کیفیت، بهترین نسخه ساخته‌شده بالاتر از ۱MB باقی مانده است؛ انتخاب با شماست.",
        sourceFile.type === "image/png"
          ? "PNG به PNG نگه داشته شد تا شفافیت تصویر حفظ شود."
          : "نسخه بهینه WebP ساخته شد؛ فایل اصلی روی دستگاه شما بدون تغییر باقی می‌ماند.",
      ].join(" · ");

      optimizeButton.style.display = "none";
      originalButton.style.display = "none";
      useOptimizedButton.style.display = "block";
      backOriginalButton.style.display = "block";
    } catch (error) {
      errorBox.style.display = "block";
      errorBox.textContent = error instanceof Error
        ? error.message
        : "بهینه‌سازی تصویر ناموفق بود. می‌توانید فایل اصلی را آپلود کنید.";
      optimizeButton.disabled = false;
      optimizeButton.textContent = "تلاش دوباره برای بهینه‌سازی";
    }
  });

  useOptimizedButton.addEventListener("click", () => {
    if (!optimized?.file) return;
    close();
    commitFileToInput(input, optimized.file);
  });

  optimizeButton.focus();
};

const shouldIntercept = (event) => {
  if (!window.location.pathname.startsWith(MEDIA_LIBRARY_PATH)) return false;
  const input = event.target;
  if (!(input instanceof HTMLInputElement) || input.type !== "file") return false;

  if (input.dataset.lbbOptimizerBypass === "1") {
    delete input.dataset.lbbOptimizerBypass;
    return false;
  }

  if (!input.files || input.files.length !== 1) return false;
  const [file] = input.files;
  return file instanceof File && SUPPORTED_TYPES.has(file.type);
};

document.addEventListener(
  "change",
  (event) => {
    if (!shouldIntercept(event)) return;

    const input = event.target;
    const [file] = input.files;

    // Intercept before FilePond/Livewire starts the HTTP upload. The selected
    // local file is handed back through a synthetic change event only after
    // the operator explicitly chooses original or optimized.
    event.preventDefault();
    event.stopImmediatePropagation();
    createDialog({ input, sourceFile: file });
  },
  true,
);
