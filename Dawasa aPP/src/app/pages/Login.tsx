import { useState } from "react";
import { useNavigate } from "react-router";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { Droplet, ArrowLeft } from "lucide-react";
import { auth } from "../../utils/api";
import { toast } from "sonner";
import { TEST_CREDENTIALS } from "../../utils/config";

export default function Login() {
  const navigate = useNavigate();
  const [mode, setMode] = useState<"login" | "signup">("login");
  const [isLoading, setIsLoading] = useState(false);
  
  // Login form
  const [ipNumber, setIpNumber] = useState("");
  const [password, setPassword] = useState("");
  
  // Signup form
  const [firstName, setFirstName] = useState("");
  const [middleName, setMiddleName] = useState("");
  const [lastName, setLastName] = useState("");
  const [location, setLocation] = useState("");
  const [signupIp, setSignupIp] = useState("");
  const [signupPassword, setSignupPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!ipNumber || !password) {
      toast.error("Please enter both IP number and password");
      return;
    }

    setIsLoading(true);
    try {
      const result = await auth.login(ipNumber, password);
      toast.success("Login successful!");
      navigate("/dashboard");
    } catch (error: any) {
      console.error("Login error:", error);
      toast.error(error.message || "Login failed. Please check your credentials.");
    } finally {
      setIsLoading(false);
    }
  };

  const handleSignup = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!firstName || !lastName || !location || !signupIp || !signupPassword) {
      toast.error("Please fill in all required fields");
      return;
    }

    if (signupPassword !== confirmPassword) {
      toast.error("Passwords do not match");
      return;
    }

    if (signupPassword.length < 6) {
      toast.error("Password must be at least 6 characters");
      return;
    }

    setIsLoading(true);
    try {
      await auth.signup({
        firstName,
        middleName,
        lastName,
        location,
        ipNumber: signupIp,
        password: signupPassword,
      });
      
      toast.success("Account created successfully! Please login.");
      
      // Switch to login mode and prefill IP
      setMode("login");
      setIpNumber(signupIp);
      setPassword("");
      
      // Clear signup form
      setFirstName("");
      setMiddleName("");
      setLastName("");
      setLocation("");
      setSignupIp("");
      setSignupPassword("");
      setConfirmPassword("");
    } catch (error: any) {
      console.error("Signup error:", error);
      toast.error(error.message || "Signup failed. Please try again.");
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-cyan-900 flex flex-col">
      {/* Status Bar Spacer */}
      <div className="h-6 bg-transparent"></div>
      
      {/* Header */}
      <div className="bg-gradient-to-b from-blue-800/50 to-transparent text-white px-6 py-8 relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-b from-white/5 to-transparent"></div>
        <div className="relative z-10 flex flex-col items-center">
          <div className="bg-white/10 backdrop-blur-md p-6 rounded-3xl mb-4 shadow-2xl border border-white/20">
            <Droplet className="w-20 h-20 text-white" fill="white" />
          </div>
          <h1 className="text-4xl font-black mb-2 tracking-tight">DAWASA</h1>
          <p className="text-blue-200 text-base font-medium">Water Services & Management</p>
        </div>
      </div>

      {/* Content */}
      <div className="flex-1 px-6 py-2 overflow-y-auto">
        <div className="max-w-sm mx-auto">
          {/* Mode Switcher */}
          <div className="bg-white/10 backdrop-blur-md rounded-3xl p-1.5 mb-8 shadow-xl border border-white/20">
            <div className="grid grid-cols-2 gap-1">
              <button
                onClick={() => setMode("login")}
                className={`py-4 rounded-2xl font-bold transition-all duration-300 text-base ${
                  mode === "login"
                    ? "bg-white text-blue-900 shadow-lg scale-105"
                    : "text-white/80 hover:text-white"
                }`}
              >
                Login
              </button>
              <button
                onClick={() => setMode("signup")}
                className={`py-4 rounded-2xl font-bold transition-all duration-300 text-base ${
                  mode === "signup"
                    ? "bg-white text-blue-900 shadow-lg scale-105"
                    : "text-white/80 hover:text-white"
                }`}
              >
                Sign Up
              </button>
            </div>
          </div>

          {/* Login Form */}
          {mode === "login" && (
            <div className="bg-white/10 backdrop-blur-md rounded-3xl shadow-2xl p-8 border border-white/20">
              <h2 className="text-3xl font-black text-white mb-8 text-center">Welcome Back</h2>
              
              <form onSubmit={handleLogin} className="space-y-6">
                <div className="space-y-3">
                  <Label htmlFor="ip" className="text-white/90 text-base font-semibold">
                    Water Meter IP Number
                  </Label>
                  <Input
                    id="ip"
                    type="text"
                    placeholder="e.g., 192.168.1.100"
                    value={ipNumber}
                    onChange={(e) => setIpNumber(e.target.value)}
                    className="h-16 rounded-2xl bg-white/20 border-white/30 text-white placeholder-white/60 text-base backdrop-blur-md focus:bg-white/30 focus:border-white/50"
                    disabled={isLoading}
                  />
                </div>

                <div className="space-y-3">
                  <Label htmlFor="password" className="text-white/90 text-base font-semibold">
                    Password
                  </Label>
                  <Input
                    id="password"
                    type="password"
                    placeholder="Enter your password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    className="h-16 rounded-2xl bg-white/20 border-white/30 text-white placeholder-white/60 text-base backdrop-blur-md focus:bg-white/30 focus:border-white/50"
                    disabled={isLoading}
                  />
                </div>

                <Button
                  type="submit"
                  className="w-full h-16 bg-white text-blue-900 hover:bg-blue-50 rounded-2xl shadow-2xl text-lg font-black transition-all duration-300 hover:scale-105 active:scale-95"
                  disabled={isLoading}
                >
                  {isLoading ? (
                    <div className="flex items-center gap-3">
                      <div className="w-6 h-6 border-3 border-blue-900/30 border-t-blue-900 rounded-full animate-spin"></div>
                      Signing in...
                    </div>
                  ) : (
                    "Sign In"
                  )}
                </Button>
              </form>

              <div className="mt-8 text-center">
                <p className="text-white/80 text-base">
                  Don't have an account?{" "}
                  <button
                    onClick={() => setMode("signup")}
                    className="text-white font-bold hover:text-blue-200 transition-colors"
                  >
                    Sign up here
                  </button>
                </p>
              </div>
            </div>
          )}

          {/* Signup Form */}
          {mode === "signup" && (
            <div className="bg-white/10 backdrop-blur-md rounded-3xl shadow-2xl p-8 border border-white/20">
              <h2 className="text-3xl font-black text-white mb-8 text-center">Create Account</h2>
              
              <form onSubmit={handleSignup} className="space-y-5">
                <div className="space-y-3">
                  <Label htmlFor="firstName" className="text-white/90 text-base font-semibold">
                    First Name <span className="text-red-400">*</span>
                  </Label>
                  <Input
                    id="firstName"
                    type="text"
                    placeholder="First name"
                    value={firstName}
                    onChange={(e) => setFirstName(e.target.value)}
                    className="h-16 rounded-2xl bg-white/20 border-white/30 text-white placeholder-white/60 text-base backdrop-blur-md focus:bg-white/30 focus:border-white/50"
                    disabled={isLoading}
                  />
                </div>

                <div className="space-y-3">
                  <Label htmlFor="middleName" className="text-white/90 text-base font-semibold">
                    Middle Name
                  </Label>
                  <Input
                    id="middleName"
                    type="text"
                    placeholder="Middle name (optional)"
                    value={middleName}
                    onChange={(e) => setMiddleName(e.target.value)}
                    className="h-16 rounded-2xl bg-white/20 border-white/30 text-white placeholder-white/60 text-base backdrop-blur-md focus:bg-white/30 focus:border-white/50"
                    disabled={isLoading}
                  />
                </div>

                <div className="space-y-3">
                  <Label htmlFor="lastName" className="text-white/90 text-base font-semibold">
                    Last Name <span className="text-red-400">*</span>
                  </Label>
                  <Input
                    id="lastName"
                    type="text"
                    placeholder="Last name"
                    value={lastName}
                    onChange={(e) => setLastName(e.target.value)}
                    className="h-16 rounded-2xl bg-white/20 border-white/30 text-white placeholder-white/60 text-base backdrop-blur-md focus:bg-white/30 focus:border-white/50"
                    disabled={isLoading}
                  />
                </div>

                <div className="space-y-3">
                  <Label htmlFor="location" className="text-white/90 text-base font-semibold">
                    Location <span className="text-red-400">*</span>
                  </Label>
                  <Input
                    id="location"
                    type="text"
                    placeholder="e.g., Kinondoni, Dar es Salaam"
                    value={location}
                    onChange={(e) => setLocation(e.target.value)}
                    className="h-16 rounded-2xl bg-white/20 border-white/30 text-white placeholder-white/60 text-base backdrop-blur-md focus:bg-white/30 focus:border-white/50"
                    disabled={isLoading}
                  />
                </div>

                <div className="space-y-3">
                  <Label htmlFor="signupIp" className="text-white/90 text-base font-semibold">
                    Water Meter IP Number <span className="text-red-400">*</span>
                  </Label>
                  <Input
                    id="signupIp"
                    type="text"
                    placeholder="e.g., 192.168.1.100"
                    value={signupIp}
                    onChange={(e) => setSignupIp(e.target.value)}
                    className="h-16 rounded-2xl bg-white/20 border-white/30 text-white placeholder-white/60 text-base backdrop-blur-md focus:bg-white/30 focus:border-white/50"
                    disabled={isLoading}
                  />
                </div>

                <div className="space-y-3">
                  <Label htmlFor="signupPassword" className="text-white/90 text-base font-semibold">
                    Password <span className="text-red-400">*</span>
                  </Label>
                  <Input
                    id="signupPassword"
                    type="password"
                    placeholder="At least 6 characters"
                    value={signupPassword}
                    onChange={(e) => setSignupPassword(e.target.value)}
                    className="h-16 rounded-2xl bg-white/20 border-white/30 text-white placeholder-white/60 text-base backdrop-blur-md focus:bg-white/30 focus:border-white/50"
                    disabled={isLoading}
                  />
                </div>

                <div className="space-y-3">
                  <Label htmlFor="confirmPassword" className="text-white/90 text-base font-semibold">
                    Confirm Password <span className="text-red-400">*</span>
                  </Label>
                  <Input
                    id="confirmPassword"
                    type="password"
                    placeholder="Re-enter password"
                    value={confirmPassword}
                    onChange={(e) => setConfirmPassword(e.target.value)}
                    className="h-16 rounded-2xl bg-white/20 border-white/30 text-white placeholder-white/60 text-base backdrop-blur-md focus:bg-white/30 focus:border-white/50"
                    disabled={isLoading}
                  />
                </div>

                <Button
                  type="submit"
                  className="w-full h-16 bg-white text-blue-900 hover:bg-blue-50 rounded-2xl shadow-2xl text-lg font-black transition-all duration-300 hover:scale-105 active:scale-95 mt-8"
                  disabled={isLoading}
                >
                  {isLoading ? (
                    <div className="flex items-center gap-3">
                      <div className="w-6 h-6 border-3 border-blue-900/30 border-t-blue-900 rounded-full animate-spin"></div>
                      Creating account...
                    </div>
                  ) : (
                    "Create Account"
                  )}
                </Button>
              </form>

              <div className="mt-8 text-center">
                <p className="text-white/80 text-base">
                  Already have an account?{" "}
                  <button
                    onClick={() => setMode("login")}
                    className="text-white font-bold hover:text-blue-200 transition-colors"
                  >
                    Login here
                  </button>
                </p>
              </div>
            </div>
          )}

          {/* Help Section */}
          <div className="mt-8 bg-white/10 backdrop-blur-md rounded-3xl shadow-xl p-6 border border-white/20">
            <p className="text-base font-bold text-white mb-3">Need Help?</p>
            <p className="text-white/80 text-base mb-4">
              Contact DAWASA Technical Support
            </p>
            <div className="space-y-3 text-base">
              <p className="text-white font-bold">📞 +255 22 245 3511</p>
              <p className="text-white/80">Mon-Fri: 8AM - 5PM</p>
            </div>
          </div>

          {/* Development Test Credentials */}
          {(import.meta as any).env?.DEV && (
            <div className="mt-6 bg-yellow-500/20 backdrop-blur-md border border-yellow-400/30 rounded-3xl p-6">
              <p className="text-base font-bold text-yellow-200 mb-4">🧪 Test Credentials (Dev Only)</p>
              <div className="space-y-3 text-sm">
                {TEST_CREDENTIALS.valid.map((cred, index) => (
                  <div key={index} className="bg-yellow-400/20 backdrop-blur-sm rounded-2xl p-3 border border-yellow-400/40">
                    <p className="font-mono text-yellow-100 text-sm">
                      IP: {cred.ipNumber} | Pass: {cred.password}
                    </p>
                    <p className="text-yellow-200">{cred.user.firstName} {cred.user.lastName}</p>
                  </div>
                ))}
              </div>
              <button
                onClick={() => {
                  const testCred = TEST_CREDENTIALS.valid[0];
                  setIpNumber(testCred.ipNumber);
                  setPassword(testCred.password);
                }}
                className="mt-4 w-full bg-yellow-500 hover:bg-yellow-400 text-yellow-900 text-sm py-3 px-4 rounded-2xl font-bold transition-all duration-300 hover:scale-105 active:scale-95"
              >
                Auto-fill Test Credentials
              </button>
            </div>
          )}

          <div className="mt-8 text-center text-xs text-white/60">
            <p>© 2026 DAWASA. Water Services Authority</p>
          </div>
        </div>
      </div>
    </div>
  );
}
