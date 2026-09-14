interface PaginationProps {
  current: number
  last: number
  onChange: (page: number) => void
}

export default function Pagination({ current, last, onChange }: PaginationProps) {
  if (last <= 1) {
    return null
  }

  return (
    <div className="pagination">
      <button
        type="button"
        className="button"
        disabled={current <= 1}
        onClick={() => onChange(current - 1)}
      >
        Previous
      </button>
      <span className="pagination__status">
        Page {current} of {last}
      </span>
      <button
        type="button"
        className="button"
        disabled={current >= last}
        onClick={() => onChange(current + 1)}
      >
        Next
      </button>
    </div>
  )
}
