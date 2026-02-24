import { RouterProvider } from 'react-router-dom'
import { router } from './router'
import { useAutoDetectLocation } from '@/lib/useAutoDetectLocation'

function AppContent() {
  useAutoDetectLocation()
  return <RouterProvider router={router} />
}

export default function App() {
  return <AppContent />
}
