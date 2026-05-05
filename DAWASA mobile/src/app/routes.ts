import { createBrowserRouter, redirect } from "react-router";
import Home from "./pages/Home";
import Onboarding from "./pages/Onboarding";
import MobileLayout from "./components/MobileLayout";
import BillStatement from "./pages/BillStatement";
import Payment from "./pages/Payment";
import Complaints from "./pages/Complaints";
import Receipts from "./pages/Receipts";

export const router = createBrowserRouter([
  {
    path: "/onboarding",
    Component: Onboarding,
  },
  {
    path: "/",
    loader: () => {
      if (!localStorage.getItem("dawasa_onboarded")) {
        return redirect("/onboarding");
      }
      return null;
    },
    Component: Home,
  },
  {
    path: "/",
    Component: MobileLayout,
    children: [
      {
        path: "statement",
        Component: BillStatement,
      },
      {
        path: "payment",
        Component: Payment,
      },
      {
        path: "complaints",
        Component: Complaints,
      },
      {
        path: "receipts",
        Component: Receipts,
      },
    ],
  },
]);