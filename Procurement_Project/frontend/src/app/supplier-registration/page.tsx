import { PublicFooter, PublicHeader } from "@/components/portal/public-shell";
import { SupplierRegistrationForm } from "@/components/portal/supplier-registration-form";
export default function RegistrationPage() { return <div className="portal-site"><PublicHeader /><main className="registration-page portal-container"><SupplierRegistrationForm /></main><PublicFooter /></div>; }
