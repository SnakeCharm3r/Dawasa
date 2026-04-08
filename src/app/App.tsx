import { RouterProvider } from "react-router";
import { router } from "./routes";
import { useCapacitor } from "../utils/capacitor";

export default function App() {
  useCapacitor();
  return <RouterProvider router={router} />;
}
