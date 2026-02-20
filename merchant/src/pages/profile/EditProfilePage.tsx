import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { ChevronLeft, Save } from 'lucide-react'
import toast from 'react-hot-toast'
import { useEffect } from 'react'
import { merchantApi, type UpdateProfilePayload } from '@/api/endpoints/merchant'

const schema = z.object({
  business_name:       z.string().min(2, 'Min 2 characters'),
  phone:               z.string().regex(/^[0-9]{10}$/, 'Enter a valid 10-digit mobile number').optional().or(z.literal('')),
  gst_number:          z.string().optional().or(z.literal('')),
  registration_number: z.string().optional().or(z.literal('')),
  business_address:    z.string().optional().or(z.literal('')),
  website_url:         z.string().url('Enter a valid URL').optional().or(z.literal('')),
})

type FormValues = z.infer<typeof schema>

function FormField({
  label, error, required, children,
}: {
  label: string; error?: string; required?: boolean; children: React.ReactNode
}) {
  return (
    <div>
      <label className="block text-xs font-semibold text-gray-600 mb-1.5">
        {label}{required && <span className="text-red-500 ml-0.5">*</span>}
      </label>
      {children}
      {error && <p className="text-red-500 text-[11px] mt-1">{error}</p>}
    </div>
  )
}

const inputClass = 'w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 bg-white placeholder-gray-300'

export default function EditProfilePage() {
  const navigate    = useNavigate()
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['merchant-profile'],
    queryFn:  () => merchantApi.getProfile().then((r) => r.data.data),
    staleTime: 5 * 60 * 1000,
  })

  const {
    register, handleSubmit, reset,
    formState: { errors, isDirty },
  } = useForm<FormValues>({ resolver: zodResolver(schema) })

  useEffect(() => {
    if (data?.profile) {
      const p = data.profile
      reset({
        business_name:       p.business_name ?? '',
        phone:               p.phone ?? '',
        gst_number:          p.gst_number ?? '',
        registration_number: p.registration_number ?? '',
        business_address:    p.business_address ?? '',
        website_url:         p.website_url ?? '',
      })
    }
  }, [data, reset])

  const mutation = useMutation({
    mutationFn: (payload: UpdateProfilePayload) => merchantApi.updateProfile(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['merchant-profile'] })
      toast.success('Profile updated')
      navigate(-1)
    },
    onError: () => toast.error('Failed to update profile'),
  })

  const onSubmit = (values: FormValues) => {
    const payload: UpdateProfilePayload = {}
    Object.entries(values).forEach(([k, v]) => {
      if (v !== '') (payload as Record<string, string>)[k] = v as string
    })
    mutation.mutate(payload)
  }

  return (
    <div className="min-h-full bg-gray-50">
      {/* Header */}
      <div className="gradient-brand px-4 pt-12 pb-5 flex items-center gap-3 fixed top-0 inset-x-0 z-30">
        <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center">
          <ChevronLeft size={20} className="text-white" />
        </button>
        <h1 className="text-white font-bold text-lg flex-1">Edit Profile</h1>
        <button
          onClick={handleSubmit(onSubmit)}
          disabled={mutation.isPending || !isDirty}
          className="h-9 px-4 bg-white text-brand-700 text-sm font-bold rounded-full flex items-center gap-1.5 disabled:opacity-50"
        >
          <Save size={14} />
          {mutation.isPending ? 'Saving…' : 'Save'}
        </button>
      </div>

      <div className="pt-24 pb-10 px-4">
        {isLoading ? (
          <div className="space-y-4">
            {Array.from({ length: 5 }).map((_, i) => (
              <div key={i} className="bg-white rounded-2xl h-16 animate-pulse" />
            ))}
          </div>
        ) : (
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <div className="bg-white rounded-2xl p-4 shadow-sm space-y-4">
              <p className="text-xs font-bold text-gray-400 uppercase tracking-wide">Business Info</p>
              <FormField label="Business Name" error={errors.business_name?.message} required>
                <input {...register('business_name')} placeholder="Your business name" className={inputClass} />
              </FormField>
              <FormField label="Business Address" error={errors.business_address?.message}>
                <textarea {...register('business_address')} placeholder="Full address" rows={3} className={`${inputClass} resize-none`} />
              </FormField>
              <FormField label="Website URL" error={errors.website_url?.message}>
                <input {...register('website_url')} placeholder="https://..." className={inputClass} />
              </FormField>
            </div>

            <div className="bg-white rounded-2xl p-4 shadow-sm space-y-4">
              <p className="text-xs font-bold text-gray-400 uppercase tracking-wide">Contact</p>
              <FormField label="Mobile Number" error={errors.phone?.message}>
                <input {...register('phone')} type="tel" placeholder="10-digit mobile" maxLength={10} className={inputClass} />
              </FormField>
            </div>

            <div className="bg-white rounded-2xl p-4 shadow-sm space-y-4">
              <p className="text-xs font-bold text-gray-400 uppercase tracking-wide">Legal</p>
              <FormField label="GST Number" error={errors.gst_number?.message}>
                <input {...register('gst_number')} placeholder="GST registration number" className={inputClass} />
              </FormField>
              <FormField label="Business Registration No." error={errors.registration_number?.message}>
                <input {...register('registration_number')} placeholder="Company / shop registration" className={inputClass} />
              </FormField>
            </div>
          </form>
        )}
      </div>
    </div>
  )
}

