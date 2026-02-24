import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ChevronLeft, Plus, Crown, Trash2, ImageOff, ArrowUp, ArrowDown } from 'lucide-react'
import { useRef, useState } from 'react'
import toast from 'react-hot-toast'
import { storeApi, type GalleryImage } from '@/api/endpoints/stores'
import { getImageUrl } from '@/lib/imageUrl'

function ImageCard({
  image, storeId, onDelete, onSetCover, onMoveUp, onMoveDown, isDeleting, isSettingCover, isFirst, isLast,
}: {
  image: GalleryImage
  storeId: number
  onDelete: () => void
  onSetCover: () => void
  onMoveUp: () => void
  onMoveDown: () => void
  isDeleting: boolean
  isSettingCover: boolean
  isFirst: boolean
  isLast: boolean
}) {
  return (
    <div className={`relative rounded-2xl overflow-hidden aspect-square ${
      image.is_cover ? 'ring-2 ring-amber-400' : ''
    }`}>
      <img src={getImageUrl(image.image_url)} alt={image.caption ?? 'Gallery'} className="w-full h-full object-cover" />
      {image.is_cover && (
        <div className="absolute top-1.5 left-1.5 bg-amber-400 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full flex items-center gap-0.5">
          <Crown size={8} />Cover
        </div>
      )}
      {/* Reorder buttons */}
      <div className="absolute top-1.5 right-1.5 flex flex-col gap-1">
        {!isFirst && (
          <button
            onClick={onMoveUp}
            className="w-6 h-6 bg-black/50 rounded-full flex items-center justify-center text-white hover:bg-black/70"
          >
            <ArrowUp size={12} />
          </button>
        )}
        {!isLast && (
          <button
            onClick={onMoveDown}
            className="w-6 h-6 bg-black/50 rounded-full flex items-center justify-center text-white hover:bg-black/70"
          >
            <ArrowDown size={12} />
          </button>
        )}
      </div>
      <div className="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/60 to-transparent p-2 flex items-center justify-between">
        {!image.is_cover && (
          <button
            onClick={onSetCover}
            disabled={isSettingCover}
            className="bg-amber-400/90 text-white text-[10px] font-bold px-2 py-1 rounded-full flex items-center gap-1 disabled:opacity-50"
          >
            <Crown size={10} />Cover
          </button>
        )}
        <button
          onClick={onDelete}
          disabled={isDeleting}
          className="ml-auto bg-red-500/90 text-white p-1.5 rounded-full disabled:opacity-50"
        >
          <Trash2 size={12} />
        </button>
      </div>
    </div>
  )
}

export default function GalleryPage() {
  const { id }      = useParams<{ id: string }>()
  const storeId     = Number(id)
  const navigate    = useNavigate()
  const queryClient = useQueryClient()
  const fileInputRef = useRef<HTMLInputElement>(null)
  const [deletingId, setDeletingId]     = useState<number | null>(null)
  const [coverSettingId, setCoverSettingId] = useState<number | null>(null)

  const { data: store, isLoading } = useQuery({
    queryKey: ['store', id],
    queryFn:  () => storeApi.get(storeId).then((r) => r.data.data),
    enabled:  !!id,
    staleTime: 2 * 60 * 1000,
  })

  const uploadMutation = useMutation({
    mutationFn: (files: File[]) => storeApi.uploadImages(storeId, files),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['store', id] })
      queryClient.invalidateQueries({ queryKey: ['stores'] })
      toast.success('Images uploaded!')
    },
    onError: () => toast.error('Upload failed'),
  })

  const deleteMutation = useMutation({
    mutationFn: (imageId: number) => {
      setDeletingId(imageId)
      return storeApi.deleteImage(storeId, imageId)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['store', id] })
      queryClient.invalidateQueries({ queryKey: ['stores'] })
      toast.success('Image removed')
    },
    onError: () => toast.error('Failed to delete image'),
    onSettled: () => setDeletingId(null),
  })

  const coverMutation = useMutation({
    mutationFn: (imageId: number) => {
      setCoverSettingId(imageId)
      return storeApi.setCover(storeId, imageId)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['store', id] })
      toast.success('Cover image updated')
    },
    onError: () => toast.error('Failed to update cover'),
    onSettled: () => setCoverSettingId(null),
  })

  const reorderMutation = useMutation({
    mutationFn: (imageIds: number[]) => storeApi.reorderGallery(storeId, imageIds),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['store', id] })
      toast.success('Order updated')
    },
    onError: () => toast.error('Failed to reorder'),
  })

  const handleMove = (index: number, direction: 'up' | 'down') => {
    const newGallery = [...gallery]
    const targetIndex = direction === 'up' ? index - 1 : index + 1
    if (targetIndex < 0 || targetIndex >= newGallery.length) return
    ;[newGallery[index], newGallery[targetIndex]] = [newGallery[targetIndex], newGallery[index]]
    const orderedIds = newGallery.map(img => img.id)
    reorderMutation.mutate(orderedIds)
  }

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = Array.from(e.target.files ?? [])
    if (files.length > 0) uploadMutation.mutate(files)
    e.target.value = ''
  }

  const gallery = store?.gallery ?? []

  return (
    <div className="min-h-full bg-gray-50 pb-10">
      {/* Header */}
      <div className="gradient-brand px-4 pt-12 pb-5 flex items-center gap-3 fixed top-0 inset-x-0 z-30">
        <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center">
          <ChevronLeft size={20} className="text-white" />
        </button>
        <div className="flex-1">
          <h1 className="text-white font-bold text-base">Gallery</h1>
          <p className="text-white/70 text-xs">{isLoading ? '…' : (store?.store_name ?? '')}</p>
        </div>
        <button
          onClick={() => fileInputRef.current?.click()}
          disabled={uploadMutation.isPending}
          className="h-9 px-3 bg-white/20 rounded-full flex items-center gap-1.5 text-white text-xs font-semibold disabled:opacity-50"
        >
          <Plus size={15} />
          {uploadMutation.isPending ? 'Uploading…' : 'Add Photos'}
        </button>
        <input
          ref={fileInputRef}
          type="file"
          accept="image/jpeg,image/png,image/webp"
          multiple
          className="hidden"
          onChange={handleFileChange}
        />
      </div>

      <div className="pt-20 px-4">
        {isLoading ? (
          <div className="grid grid-cols-2 gap-3 mt-4">
            {[1,2,3,4].map((i) => <div key={i} className="aspect-square bg-gray-200 rounded-2xl animate-pulse" />)}
          </div>
        ) : gallery.length === 0 ? (
          <div className="flex flex-col items-center justify-center pt-24 gap-3 text-center">
            <div className="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
              <ImageOff size={28} className="text-gray-300" />
            </div>
            <p className="font-semibold text-gray-500">No photos yet</p>
            <p className="text-xs text-gray-400">Add photos to showcase your store</p>
            <button
              onClick={() => fileInputRef.current?.click()}
              className="mt-2 px-5 py-2.5 gradient-brand text-white text-sm font-semibold rounded-xl"
            >Upload Photos</button>
          </div>
        ) : (
          <>
            <p className="text-xs text-gray-400 mt-3 mb-3">
              {gallery.length} photo{gallery.length !== 1 ? 's' : ''} · Tap a photo to set as cover
            </p>
            <div className="grid grid-cols-2 gap-3">
              {gallery.map((img, idx) => (
                <ImageCard
                  key={img.id}
                  image={img}
                  storeId={storeId}
                  onDelete={() => deleteMutation.mutate(img.id)}
                  onSetCover={() => coverMutation.mutate(img.id)}
                  onMoveUp={() => handleMove(idx, 'up')}
                  onMoveDown={() => handleMove(idx, 'down')}
                  isDeleting={deletingId === img.id}
                  isSettingCover={coverSettingId === img.id}
                  isFirst={idx === 0}
                  isLast={idx === gallery.length - 1}
                />
              ))}
              {/* Upload tile */}
              <button
                onClick={() => fileInputRef.current?.click()}
                disabled={uploadMutation.isPending}
                className="aspect-square rounded-2xl border-2 border-dashed border-gray-200 flex flex-col items-center justify-center gap-2 text-gray-300 hover:border-brand-400 hover:text-brand-400 transition-colors disabled:opacity-50"
              >
                <Plus size={24} />
                <span className="text-xs font-medium">Add</span>
              </button>
            </div>
          </>
        )}
      </div>
    </div>
  )
}
