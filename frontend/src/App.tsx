import { Route, Routes } from 'react-router-dom'
import Layout from './components/Layout'
import AssetDetailPage from './pages/AssetDetailPage'
import AssetsPage from './pages/AssetsPage'
import EmployeesPage from './pages/EmployeesPage'
import HomePage from './pages/HomePage'

export default function App() {
  return (
    <Routes>
      <Route element={<Layout />}>
        <Route path="/" element={<HomePage />} />
        <Route path="/assets" element={<AssetsPage />} />
        <Route path="/assets/:assetId" element={<AssetDetailPage />} />
        <Route path="/employees" element={<EmployeesPage />} />
      </Route>
    </Routes>
  )
}
