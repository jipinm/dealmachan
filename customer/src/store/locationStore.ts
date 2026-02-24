import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export interface LocationState {
  cityId: number | null
  cityName: string | null
  areaId: number | null
  areaName: string | null
  /** Whether the location picker modal is open */
  modalOpen: boolean
  /** True once auto-detection has been attempted (persisted so it only runs once) */
  detectionAttempted: boolean
}

interface LocationActions {
  setLocation(cityId: number, cityName: string, areaId?: number | null, areaName?: string | null): void
  setArea(areaId: number | null, areaName: string | null): void
  clearLocation(): void
  openLocationModal(): void
  closeLocationModal(): void
  setDetectionAttempted(val: boolean): void
}

export const useLocationStore = create<LocationState & LocationActions>()(
  persist(
    (set) => ({
      cityId:   null,
      cityName: null,
      areaId:   null,
      areaName: null,
      modalOpen: false,
      detectionAttempted: false,

      setLocation: (cityId, cityName, areaId = null, areaName = null) => {
        set({ cityId, cityName, areaId, areaName })
      },

      setArea: (areaId, areaName) => {
        set({ areaId, areaName })
      },

      clearLocation: () => {
        set({ cityId: null, cityName: null, areaId: null, areaName: null })
      },

      openLocationModal: () => set({ modalOpen: true }),
      closeLocationModal: () => set({ modalOpen: false }),
      setDetectionAttempted: (val) => set({ detectionAttempted: val }),
    }),
    {
      name: 'dm-customer-location',
      partialize: (state) => ({
        cityId: state.cityId,
        cityName: state.cityName,
        areaId: state.areaId,
        areaName: state.areaName,
        detectionAttempted: state.detectionAttempted,
      }),
    },
  ),
)
