import { createBrowserRouter } from "react-router";
import Login from "./pages/Login";
import Dashboard from "./pages/Dashboard";
import Bills from "./pages/Bills";
import Payment from "./pages/Payment";
import Complaints from "./pages/Complaints";
import MobileLayout from "./components/MobileLayout";

export const router = createBrowserRouter([
  {
    path: "/",
    Component: Login,
  },
  {
    path: "/",
    Component: MobileLayout,
    children: [
      {
        path: "dashboard",
        Component: Dashboard,
      },
      {
        path: "bills",
        Component: Bills,
      },
      {
        path: "payment",
        Component: Payment,
      },
      {
        path: "complaints",
        Component: Complaints,
      },
    ],
  },
]);