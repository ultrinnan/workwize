import { NavLink, Outlet } from 'react-router-dom'

export default function Layout() {
  return (
    <div className="app">
      <header className="topbar">
        <nav className="topbar__nav">
          <NavLink to="/" end>
            Home
          </NavLink>
          <NavLink to="/assets">Assets</NavLink>
          <NavLink to="/employees">Employees</NavLink>
        </nav>
      </header>
      <main className="app__main">
        <Outlet />
      </main>
    </div>
  )
}
