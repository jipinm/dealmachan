import { useState, useEffect } from 'react'

/**
 * Returns a debounced version of `value` that only updates after `delay` ms
 * of inactivity. Use this to avoid firing API calls on every keystroke.
 *
 * @example
 * const debouncedQ = useDebounce(query, 350)
 * useQuery({ queryKey: ['search', debouncedQ], ... })
 */
export function useDebounce<T>(value: T, delay = 350): T {
  const [debounced, setDebounced] = useState<T>(value)

  useEffect(() => {
    const t = setTimeout(() => setDebounced(value), delay)
    return () => clearTimeout(t)
  }, [value, delay])

  return debounced
}
