import { create } from 'zustand'

interface BottomSheet {
  id: string
  content: React.ReactNode
}

interface UiState {
  globalLoading: boolean
  activeSheet: BottomSheet | null
  toastQueue: string[]
}

interface UiActions {
  setGlobalLoading(v: boolean): void
  openSheet(sheet: BottomSheet): void
  closeSheet(): void
}

// React import kept type-only — no JSX in this file
import type React from 'react'

export const useUiStore = create<UiState & UiActions>()((set) => ({
  globalLoading: false,
  activeSheet: null,
  toastQueue: [],

  setGlobalLoading: (v) => set({ globalLoading: v }),
  openSheet: (sheet) => set({ activeSheet: sheet }),
  closeSheet: () => set({ activeSheet: null }),
}))
