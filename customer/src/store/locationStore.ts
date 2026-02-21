import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export interface LocationState {
  cityId: number | null
  cityName: string | null
  areaId: number | null
  areaName: string | null
}

interface LocationActions {
  setLocation(cityId: number, cityName: string, areaId?: number | null, areaName?: string | null): void
  setArea(areaId: number | null, areaName: string | null): void
  clearLocation(): void
}

export const useLocationStore = create<LocationState & LocationActions>()(
  persist(
    (set) => ({
      cityId:   null,
      cityName: null,
      areaId:   null,
      areaName: null,

      setLocation: (cityId, cityName, areaId = null, areaName = null) => {
        set({ cityId, cityName, areaId, areaName })
      },

      setArea: (areaId, areaName) => {
        set({ areaId, areaName })
      },

      clearLocation: () => {
        set({ cityId: null, cityName: null, areaId: null, areaName: null })
      },
    }),
    {
      name: 'dm-customer-location',
    },
  ),
)
