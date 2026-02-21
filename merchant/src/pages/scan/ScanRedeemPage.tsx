import { useState, useRef, useEffect, useCallback } from 'react'
import { useMutation, useQuery } from '@tanstack/react-query'
import { Camera, Keyboard, X, CheckCircle, AlertCircle, ScanLine, Search, Ticket, Zap, Gift } from 'lucide-react'
import toast from 'react-hot-toast'
import jsQR from 'jsqr'
import { couponApi, type RedeemResult, type RedeemableItem, type CustomerLookupResult } from '@/api/endpoints/coupons'
import { flashDiscountApi } from '@/api/endpoints/flashDiscounts'
import { storeCouponApi } from '@/api/endpoints/storeCoupons'

type Mode = 'idle' | 'camera' | 'manual' | 'customer-lookup'
type Step = 'enter-code' | 'confirm' | 'success'

export default function ScanRedeemPage() {
  const [mode, setMode]           = useState<Mode>('idle')
  const [step, setStep]           = useState<Step>('enter-code')
  const [code, setCode]           = useState('')
  const [customerPhone, setCustomerPhone] = useState('')
  const [txAmount, setTxAmount]   = useState('')
  const [result, setResult]       = useState<RedeemResult | null>(null)
  const [cameraError, setCameraError] = useState('')
  const [lookupPhone, setLookupPhone] = useState('')
  const [lookupSearched, setLookupSearched] = useState(false)
  const [lookupResult, setLookupResult] = useState<CustomerLookupResult | null>(null)
  const [redeemingItem, setRedeemingItem] = useState<RedeemableItem | null>(null)
  const [itemTxAmount, setItemTxAmount] = useState('')

  const videoRef  = useRef<HTMLVideoElement>(null)
  const canvasRef = useRef<HTMLCanvasElement>(null)
  const streamRef = useRef<MediaStream | null>(null)
  const rafRef    = useRef<number>(0)
  const scannedRef = useRef(false)

  // ── Camera scanning ──────────────────────────────────────────────────────────
  const stopCamera = useCallback(() => {
    cancelAnimationFrame(rafRef.current)
    streamRef.current?.getTracks().forEach((t) => t.stop())
    streamRef.current = null
    scannedRef.current = false
  }, [])

  const tick = useCallback(() => {
    const video  = videoRef.current
    const canvas = canvasRef.current
    if (!video || !canvas || video.readyState !== video.HAVE_ENOUGH_DATA) {
      rafRef.current = requestAnimationFrame(tick)
      return
    }
    canvas.width  = video.videoWidth
    canvas.height = video.videoHeight
    const ctx = canvas.getContext('2d')!
    ctx.drawImage(video, 0, 0)
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height)
    const qrCode = jsQR(imageData.data, imageData.width, imageData.height)
    if (qrCode && !scannedRef.current) {
      scannedRef.current = true
      stopCamera()
      // Extract plain code from QR (could be URL or raw code)
      const raw = qrCode.data
      const extracted = raw.includes('code=') ? new URL(raw).searchParams.get('code') ?? raw : raw
      setCode(extracted.toUpperCase())
      setMode('manual')
      setStep('confirm')
      toast.success('QR code scanned!')
      return
    }
    rafRef.current = requestAnimationFrame(tick)
  }, [stopCamera])

  const startCamera = useCallback(async () => {
    setCameraError('')
    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' },
      })
      streamRef.current = stream
      if (videoRef.current) {
        videoRef.current.srcObject = stream
        videoRef.current.play()
      }
      rafRef.current = requestAnimationFrame(tick)
    } catch {
      setCameraError('Cannot access camera. Use manual entry below.')
      setMode('manual')
    }
  }, [tick])

  useEffect(() => {
    if (mode === 'camera') startCamera()
    return () => stopCamera()
  }, [mode, startCamera, stopCamera])

  // ── Mutation ─────────────────────────────────────────────────────────────────
  const mutation = useMutation({
    mutationFn: () =>
      couponApi.manualRedeem({
        coupon_code: code.trim().toUpperCase(),
        customer_phone: customerPhone || undefined,
        transaction_amount: txAmount ? parseFloat(txAmount) : undefined,
      }),
    onSuccess: (res) => {
      setResult(res.data.data)
      setStep('success')
    },
    onError: (e: any) => {
      toast.error(e?.response?.data?.message ?? 'Redemption failed')
    },
  })

  // ── Customer Lookup ──────────────────────────────────────────────────────────
  const lookupMutation = useMutation({
    mutationFn: (phone: string) => couponApi.lookupCustomer(phone),
    onSuccess: (res) => {
      setLookupResult(res.data.data)
      setLookupSearched(true)
    },
    onError: (e: any) => {
      toast.error(e?.response?.data?.message ?? 'Customer not found')
      setLookupResult(null)
      setLookupSearched(true)
    },
  })

  const redeemItemMutation = useMutation({
    mutationFn: async ({ item, txAmt }: { item: RedeemableItem; txAmt?: number }) => {
      if (item.type === 'platform_coupon') {
        return couponApi.manualRedeem({
          coupon_code: item.coupon_code!,
          customer_id: lookupResult!.customer.id,
          transaction_amount: txAmt,
        })
      } else if (item.type === 'store_coupon') {
        await storeCouponApi.redeem(item.id, {
          customer_phone: lookupResult!.customer.phone ?? '',
          transaction_amount: txAmt,
        })
      } else {
        // flash_discount
        await flashDiscountApi.redeem(item.id, {
          customer_phone: lookupResult!.customer.phone ?? '',
          transaction_amount: txAmt,
        })
      }
    },
    onSuccess: (res) => {
      toast.success('Redeemed successfully!')
      setRedeemingItem(null)
      setItemTxAmount('')
      // Refresh lookup
      if (lookupResult?.customer.phone) {
        lookupMutation.mutate(lookupResult.customer.phone)
      }
    },
    onError: (e: any) => {
      toast.error(e?.response?.data?.message ?? 'Redemption failed')
    },
  })

  const reset = () => {
    setStep('enter-code')
    setCode('')
    setCustomerPhone('')
    setTxAmount('')
    setResult(null)
    setMode('idle')
    setLookupPhone('')
    setLookupSearched(false)
    setLookupResult(null)
    setRedeemingItem(null)
    setItemTxAmount('')
    scannedRef.current = false
  }

  // ── Render ────────────────────────────────────────────────────────────────────
  return (
    <div className="min-h-full bg-gray-50">
      {/* Header */}
      <div className="gradient-brand px-5 pt-14 pb-6">
        <h1 className="text-white font-bold text-xl">Scan & Redeem</h1>
        <p className="text-white/70 text-sm mt-0.5">Redeem a customer's coupon</p>
      </div>

      <div className="px-4 py-4 space-y-4">

        {step === 'success' && result && (
          <div className="bg-white rounded-2xl shadow-sm p-6 flex flex-col items-center gap-4">
            <div className="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center">
              <CheckCircle size={36} className="text-emerald-500" />
            </div>
            <div className="text-center">
              <p className="text-lg font-extrabold text-gray-900">Redeemed!</p>
              <p className="text-sm text-gray-500 mt-1">{result.coupon_title}</p>
            </div>
            <div className="bg-emerald-50 rounded-2xl px-6 py-4 text-center w-full">
              <p className="text-sm text-gray-500">Discount Applied</p>
              <p className="text-4xl font-extrabold text-emerald-600">₹{result.discount_amount.toFixed(2)}</p>
              {result.transaction_amount && (
                <p className="text-xs text-gray-400 mt-1">
                  on ₹{result.transaction_amount.toFixed(2)} transaction
                </p>
              )}
            </div>
            <p className="font-mono text-sm font-bold text-brand-700 tracking-widest bg-brand-50 px-4 py-2 rounded-xl">
              {result.coupon_code}
            </p>
            <button
              onClick={reset}
              className="w-full py-3 bg-brand-600 text-white font-bold rounded-2xl hover:bg-brand-700 transition-colors"
            >
              Scan Another
            </button>
          </div>
        )}

        {step !== 'success' && (
          <>
            {/* Mode chooser */}
            {mode === 'idle' && (
              <div className="space-y-3">
                <div className="grid grid-cols-2 gap-3">
                  <button
                    onClick={() => { setMode('camera'); setStep('enter-code') }}
                    className="bg-white rounded-2xl shadow-sm p-5 flex flex-col items-center gap-2 hover:shadow-md transition-shadow active:scale-95"
                  >
                    <div className="w-12 h-12 bg-brand-100 rounded-2xl flex items-center justify-center">
                      <Camera size={24} className="text-brand-600" />
                    </div>
                    <p className="text-sm font-bold text-gray-800">Scan QR Code</p>
                    <p className="text-xs text-gray-400 text-center">Point camera at customer's coupon QR</p>
                  </button>
                  <button
                    onClick={() => { setMode('manual'); setStep('enter-code') }}
                    className="bg-white rounded-2xl shadow-sm p-5 flex flex-col items-center gap-2 hover:shadow-md transition-shadow active:scale-95"
                  >
                    <div className="w-12 h-12 bg-indigo-100 rounded-2xl flex items-center justify-center">
                      <Keyboard size={24} className="text-indigo-500" />
                    </div>
                    <p className="text-sm font-bold text-gray-800">Manual Entry</p>
                    <p className="text-xs text-gray-400 text-center">Type the coupon code manually</p>
                  </button>
                </div>
                <button
                  onClick={() => setMode('customer-lookup')}
                  className="w-full bg-white rounded-2xl shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition-shadow active:scale-95"
                >
                  <div className="w-12 h-12 bg-emerald-100 rounded-2xl flex items-center justify-center shrink-0">
                    <Search size={24} className="text-emerald-600" />
                  </div>
                  <div className="text-left">
                    <p className="text-sm font-bold text-gray-800">Customer Lookup</p>
                    <p className="text-xs text-gray-400">Search customer by phone and see all their redeemable offers</p>
                  </div>
                </button>
              </div>
            )}

            {cameraError && (
              <div className="bg-amber-50 border border-amber-200 rounded-2xl px-4 py-3 flex gap-2">
                <AlertCircle size={16} className="text-amber-500 shrink-0 mt-0.5" />
                <p className="text-sm text-amber-700">{cameraError}</p>
              </div>
            )}

            {/* Camera view */}
            {mode === 'camera' && (
              <div className="bg-black rounded-2xl overflow-hidden relative shadow-lg">
                <video
                  ref={videoRef}
                  muted
                  playsInline
                  className="w-full aspect-square object-cover"
                />
                <canvas ref={canvasRef} className="hidden" />
                {/* Scan overlay */}
                <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                  <div className="w-52 h-52 border-2 border-white/60 rounded-2xl relative">
                    <span className="absolute top-0 left-0 w-6 h-6 border-t-4 border-l-4 border-brand-400 rounded-tl-lg" />
                    <span className="absolute top-0 right-0 w-6 h-6 border-t-4 border-r-4 border-brand-400 rounded-tr-lg" />
                    <span className="absolute bottom-0 left-0 w-6 h-6 border-b-4 border-l-4 border-brand-400 rounded-bl-lg" />
                    <span className="absolute bottom-0 right-0 w-6 h-6 border-b-4 border-r-4 border-brand-400 rounded-br-lg" />
                    <div className="absolute inset-0 flex items-center justify-center">
                      <ScanLine size={28} className="text-white/70" />
                    </div>
                  </div>
                </div>
                <button
                  onClick={() => { stopCamera(); setMode('idle') }}
                  className="absolute top-3 right-3 w-8 h-8 bg-black/50 rounded-full flex items-center justify-center text-white"
                >
                  <X size={16} />
                </button>
              </div>
            )}

            {/* Manual entry / confirm form */}
            {mode === 'manual' && (
              <div className="bg-white rounded-2xl shadow-sm p-4 space-y-4">
                <div className="flex items-center justify-between">
                  <p className="text-sm font-bold text-gray-700">
                    {step === 'enter-code' ? 'Enter Coupon Code' : 'Confirm Redemption'}
                  </p>
                  <button onClick={reset} className="text-gray-400 hover:text-gray-600">
                    <X size={18} />
                  </button>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-gray-500 mb-1">Coupon Code *</label>
                  <input
                    value={code}
                    onChange={(e) => setCode(e.target.value.toUpperCase())}
                    placeholder="e.g. SAVE20"
                    className="w-full rounded-xl border border-gray-200 px-3 py-2.5 font-mono text-sm font-bold uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-brand-400"
                  />
                </div>

                {step === 'confirm' && (
                  <>
                    <div>
                      <label className="block text-xs font-semibold text-gray-500 mb-1">Customer Phone *</label>
                      <input
                        value={customerPhone}
                        onChange={(e) => setCustomerPhone(e.target.value)}
                        placeholder="10-digit mobile number"
                        type="tel"
                        inputMode="numeric"
                        className="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
                      />
                      <p className="text-xs text-gray-400 mt-1">Used to link redemption to customer account</p>
                    </div>
                    <div>
                      <label className="block text-xs font-semibold text-gray-500 mb-1">Transaction Amount (₹) — optional</label>
                      <input
                        value={txAmount}
                        onChange={(e) => setTxAmount(e.target.value)}
                        placeholder="e.g. 500"
                        type="number"
                        min="0"
                        className="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
                      />
                    </div>
                    <button
                      onClick={() => mutation.mutate()}
                      disabled={mutation.isPending || !customerPhone}
                      className="w-full bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white font-bold py-3.5 rounded-2xl transition-colors"
                    >
                      {mutation.isPending ? 'Processing…' : '✓ Confirm Redemption'}
                    </button>
                  </>
                )}

                {step === 'enter-code' && (
                  <button
                    onClick={() => {
                      if (!code.trim()) { toast.error('Enter a coupon code'); return }
                      setStep('confirm')
                    }}
                    className="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-3.5 rounded-2xl transition-colors"
                  >
                    Next →
                  </button>
                )}
              </div>
            )}

            {/* Customer Lookup mode */}
            {mode === 'customer-lookup' && (
              <div className="space-y-4">
                <div className="bg-white rounded-2xl shadow-sm p-4 space-y-4">
                  <div className="flex items-center justify-between">
                    <p className="text-sm font-bold text-gray-700">Customer Lookup</p>
                    <button onClick={reset} className="text-gray-400 hover:text-gray-600">
                      <X size={18} />
                    </button>
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-gray-500 mb-1">Customer Phone / Card Number *</label>
                    <div className="flex gap-2">
                      <input
                        value={lookupPhone}
                        onChange={(e) => setLookupPhone(e.target.value)}
                        placeholder="Enter phone or card number"
                        type="tel"
                        inputMode="numeric"
                        className="flex-1 rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
                      />
                      <button
                        onClick={() => {
                          if (!lookupPhone.trim()) { toast.error('Enter a phone number'); return }
                          lookupMutation.mutate(lookupPhone.trim())
                        }}
                        disabled={lookupMutation.isPending}
                        className="px-4 bg-brand-600 text-white font-bold rounded-xl text-sm disabled:opacity-50"
                      >
                        {lookupMutation.isPending ? '…' : 'Search'}
                      </button>
                    </div>
                  </div>
                </div>

                {/* Lookup results */}
                {lookupSearched && !lookupResult && (
                  <div className="bg-white rounded-2xl shadow-sm p-6 text-center">
                    <AlertCircle size={32} className="text-gray-300 mx-auto mb-2" />
                    <p className="text-gray-500 font-medium">Customer not found</p>
                    <p className="text-gray-400 text-sm mt-1">Check the phone number and try again</p>
                  </div>
                )}

                {lookupResult && (
                  <>
                    {/* Customer info card */}
                    <div className="bg-white rounded-2xl shadow-sm p-4 flex items-center gap-3">
                      <div className="w-12 h-12 bg-brand-100 rounded-full flex items-center justify-center">
                        <span className="text-brand-700 font-bold">
                          {lookupResult.customer.name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()}
                        </span>
                      </div>
                      <div>
                        <p className="text-sm font-bold text-gray-800">{lookupResult.customer.name}</p>
                        <p className="text-xs text-gray-400">{lookupResult.customer.phone ?? lookupResult.customer.email ?? ''}</p>
                      </div>
                    </div>

                    {/* Redeemable items */}
                    {lookupResult.redeemable_items.length === 0 ? (
                      <div className="bg-white rounded-2xl shadow-sm p-6 text-center">
                        <Ticket size={28} className="text-gray-300 mx-auto mb-2" />
                        <p className="text-gray-500 font-medium text-sm">No redeemable offers</p>
                        <p className="text-gray-400 text-xs mt-1">This customer has no active coupons or discounts</p>
                      </div>
                    ) : (
                      <div className="space-y-2">
                        <p className="text-xs font-bold text-gray-400 uppercase tracking-wide px-1">
                          {lookupResult.redeemable_items.length} Redeemable Offer{lookupResult.redeemable_items.length !== 1 ? 's' : ''}
                        </p>
                        {lookupResult.redeemable_items.map((item) => {
                          const typeLabel = item.type === 'platform_coupon' ? 'Platform Coupon'
                            : item.type === 'store_coupon' ? 'Store Coupon'
                            : 'Flash Discount'
                          const TypeIcon = item.type === 'flash_discount' ? Zap
                            : item.type === 'store_coupon' ? Gift
                            : Ticket
                          const typeBg = item.type === 'flash_discount' ? 'bg-orange-100 text-orange-600'
                            : item.type === 'store_coupon' ? 'bg-purple-100 text-purple-600'
                            : 'bg-brand-100 text-brand-600'
                          const discountLabel = item.discount_type === 'percentage'
                            ? `${parseFloat(item.discount_value).toFixed(0)}% OFF`
                            : `₹${parseFloat(item.discount_value).toFixed(0)} OFF`

                          return (
                            <div key={`${item.type}-${item.id}`} className="bg-white rounded-2xl shadow-sm p-4">
                              <div className="flex items-start gap-3">
                                <div className={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 ${typeBg}`}>
                                  <TypeIcon size={18} />
                                </div>
                                <div className="flex-1 min-w-0">
                                  <div className="flex items-center gap-2">
                                    <p className="text-sm font-semibold text-gray-800 truncate">{item.title}</p>
                                    <span className="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full shrink-0">{discountLabel}</span>
                                  </div>
                                  <p className="text-xs text-gray-400 mt-0.5">{typeLabel}{item.store_name ? ` · ${item.store_name}` : ''}</p>
                                </div>
                              </div>

                              {redeemingItem?.id === item.id && redeemingItem?.type === item.type ? (
                                <div className="mt-3 space-y-3">
                                  <div>
                                    <label className="block text-xs font-semibold text-gray-500 mb-1">Transaction Amount (₹) — optional</label>
                                    <input
                                      value={itemTxAmount}
                                      onChange={(e) => setItemTxAmount(e.target.value)}
                                      placeholder="e.g. 500"
                                      type="number"
                                      min="0"
                                      className="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
                                    />
                                  </div>
                                  <div className="flex gap-2">
                                    <button
                                      onClick={() => redeemItemMutation.mutate({
                                        item,
                                        txAmt: itemTxAmount ? parseFloat(itemTxAmount) : undefined,
                                      })}
                                      disabled={redeemItemMutation.isPending}
                                      className="flex-1 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold py-2.5 rounded-xl text-sm"
                                    >
                                      {redeemItemMutation.isPending ? 'Processing…' : '✓ Confirm'}
                                    </button>
                                    <button
                                      onClick={() => { setRedeemingItem(null); setItemTxAmount('') }}
                                      className="px-4 border border-gray-200 rounded-xl text-sm text-gray-600"
                                    >
                                      Cancel
                                    </button>
                                  </div>
                                </div>
                              ) : (
                                <button
                                  onClick={() => setRedeemingItem(item)}
                                  className="mt-3 w-full py-2 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-semibold text-emerald-700 hover:bg-emerald-100"
                                >
                                  Redeem This
                                </button>
                              )}
                            </div>
                          )
                        })}
                      </div>
                    )}
                  </>
                )}
              </div>
            )}
          </>
        )}
      </div>
    </div>
  )
}
