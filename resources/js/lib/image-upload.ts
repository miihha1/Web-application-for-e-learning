export async function prepareImageUpload(file: File, maxBytes = 1_900_000): Promise<File> {
  if (!file.type.startsWith('image/')) {
    return file
  }

  if (file.size <= maxBytes) {
    return file
  }

  const dataUrl = await readFileAsDataUrl(file)
  const image = await loadImage(dataUrl)

  let width = image.width
  let height = image.height
  const maxDimension = 1600

  if (width > maxDimension || height > maxDimension) {
    const ratio = Math.min(maxDimension / width, maxDimension / height)
    width = Math.round(width * ratio)
    height = Math.round(height * ratio)
  }

  const canvas = document.createElement('canvas')
  canvas.width = width
  canvas.height = height

  const context = canvas.getContext('2d')
  if (!context) {
    return file
  }

  context.drawImage(image, 0, 0, width, height)

  let quality = 0.9
  let blob = await canvasToBlob(canvas, 'image/jpeg', quality)

  while (blob.size > maxBytes && quality > 0.45) {
    quality -= 0.1
    blob = await canvasToBlob(canvas, 'image/jpeg', quality)
  }

  const name = file.name.replace(/\.[^.]+$/, '') + '.jpg'

  return new File([blob], name, {
    type: 'image/jpeg',
    lastModified: Date.now(),
  })
}

function readFileAsDataUrl(file: File): Promise<string> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => resolve(String(reader.result))
    reader.onerror = () => reject(reader.error)
    reader.readAsDataURL(file)
  })
}

function loadImage(src: string): Promise<HTMLImageElement> {
  return new Promise((resolve, reject) => {
    const image = new Image()
    image.onload = () => resolve(image)
    image.onerror = reject
    image.src = src
  })
}

function canvasToBlob(canvas: HTMLCanvasElement, type: string, quality: number): Promise<Blob> {
  return new Promise((resolve, reject) => {
    canvas.toBlob(blob => {
      if (!blob) {
        reject(new Error('Failed to create image blob'))
        return
      }

      resolve(blob)
    }, type, quality)
  })
}
