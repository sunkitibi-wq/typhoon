import { Head, useForm } from '@inertiajs/react';
import { CheckCircle2, ChevronRight, ChevronLeft, AlertCircle } from 'lucide-react';
import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

type Props = {
    passwordRules: string;
};

type RegisterStep = 'email' | 'password' | 'profile' | 'kyc' | 'confirmation';

const STEPS: { id: RegisterStep; title: string; description: string }[] = [
    { id: 'email', title: 'Email', description: 'Create your account' },
    { id: 'password', title: 'Password', description: 'Secure your account' },
    { id: 'profile', title: 'Profile', description: 'Tell us about yourself' },
    { id: 'kyc', title: 'Identity', description: 'Verify your identity' },
    { id: 'confirmation', title: 'Confirmation', description: 'Review and confirm' },
];

export default function Register({ passwordRules }: Props) {
    const [currentStep, setCurrentStep] = useState<RegisterStep>('email');
    const [completedSteps, setCompletedSteps] = useState<RegisterStep[]>([]);
    const [clientErrors, setClientErrors] = useState<Record<string, string>>({});

    const form = useForm({
        email: '',
        password: '',
        password_confirmation: '',
        name: '',
        phone_number: '',
        date_of_birth: '',
        country: '',
        id_type: '',
        id_number: '',
        postal_code: '',
        address_line1: '',
        city: '',
        state: '',
        source_of_funds: '',
        occupation: '',
    });

    const validateStep = (data: Record<string, string>, step: RegisterStep): boolean => {
        const newErrors: Record<string, string> = {};

        switch (step) {
            case 'email': {
                if (!data.email) {
                    newErrors.email = 'Email is required';
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
                    newErrors.email = 'Invalid email format';
                }

                break;
            }
            case 'password': {
                if (!data.password) {
                    newErrors.password = 'Password is required';
                } else if (data.password.length < 8) {
                    newErrors.password = 'Password must be at least 8 characters';
                }

                if (data.password !== data.password_confirmation) {
                    newErrors.password_confirmation = 'Passwords do not match';
                }

                break;
            }
            case 'profile': {
                if (!data.name) {
                    newErrors.name = 'Name is required';
                }

                if (!data.phone_number) {
                    newErrors.phone_number = 'Phone number is required';
                }

                if (!data.date_of_birth) {
                    newErrors.date_of_birth = 'Date of birth is required';
                }

                break;
            }
            case 'kyc': {
                if (!data.country) {
                    newErrors.country = 'Country is required';
                }

                if (!data.id_type) {
                    newErrors.id_type = 'ID type is required';
                }

                if (!data.id_number) {
                    newErrors.id_number = 'ID number is required';
                }

                if (!data.address_line1) {
                    newErrors.address_line1 = 'Address is required';
                }

                if (!data.city) {
                    newErrors.city = 'City is required';
                }

                if (!data.postal_code) {
                    newErrors.postal_code = 'Postal code is required';
                }

                break;
            }
        }

        setClientErrors(newErrors);

        return Object.keys(newErrors).length === 0;
    };

    const handleNext = (data: Record<string, string>) => {
        if (validateStep(data, currentStep)) {
            setCompletedSteps([...new Set([...completedSteps, currentStep])]);

            const currentIndex = STEPS.findIndex(s => s.id === currentStep);

            if (currentIndex < STEPS.length - 1) {
                setCurrentStep(STEPS[currentIndex + 1].id);
            }
        }
    };

    const handlePrevious = () => {
        const currentIndex = STEPS.findIndex(s => s.id === currentStep);

        if (currentIndex > 0) {
            setCurrentStep(STEPS[currentIndex - 1].id);
        }
    };

    const goToStep = (stepId: RegisterStep) => {
        const currentIndex = STEPS.findIndex(s => s.id === currentStep);

        const targetIndex = STEPS.findIndex(s => s.id === stepId);

        if (targetIndex <= currentIndex || completedSteps.includes(stepId)) {
            setCurrentStep(stepId);
        }
    };

    const currentStepIndex = STEPS.findIndex(s => s.id === currentStep);

    const stepData = STEPS[currentStepIndex];
    const isLastStep = currentStep === 'confirmation';
    const isFirstStep = currentStep === 'email';
    const stepProgress = Math.round(((currentStepIndex + 1) / STEPS.length) * 100);
    const serverError = Object.values(form.errors)[0] ?? '';
    const [isLargeScreen, setIsLargeScreen] = useState(false);

    useEffect(() => {
        const mql = window.matchMedia('(min-width: 1024px)');
        setIsLargeScreen(mql.matches);
        const handler = (e: MediaQueryListEvent) => setIsLargeScreen(e.matches);
        mql.addEventListener('change', handler);
        return () => mql.removeEventListener('change', handler);
    }, []);

    return (
        <>
            <Head title="Register" />
            <div
                className="min-h-screen text-slate-100 px-4 py-8 sm:px-6 lg:px-8"
                style={{
                    background: 'radial-gradient(circle at top left, rgba(56,189,248,0.18), transparent 26%), radial-gradient(circle at top right, rgba(14,165,233,0.14), transparent 28%), radial-gradient(circle at bottom, rgba(15,23,42,0.95), transparent 55%), #020617',
                }}
            >
                <div className="mx-auto max-w-7xl">
                    <div className="mb-6 text-center lg:text-left">
                        <p className="text-sm font-semibold uppercase tracking-[0.25em] text-cyan-300">Typhoon Banking</p>
                        <h1 className="mt-3 text-2xl font-semibold text-white sm:text-3xl">Create your account</h1>
                        <p className="mt-2 text-sm leading-6 text-slate-400">
                            Fast onboarding, built-in identity checks, and a modern banking experience ready for your first deposit.
                        </p>
                    </div>

                    <div className="grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
                        <section className="rounded-2xl border border-slate-800 bg-slate-900/90 p-5 shadow-lg shadow-slate-950/20">
                            <div className="mb-6 flex items-start gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-cyan-500/10 text-cyan-300 ring-1 ring-cyan-500/20">
                                    <CheckCircle2 className="h-5 w-5" />
                                </div>
                                <div>
                                    <p className="text-xs uppercase tracking-[0.3em] text-slate-500">Registration flow</p>
                                    <h2 className="mt-2 text-base font-semibold text-white">Onboarding</h2>
                                </div>
                            </div>

                            <div className="space-y-2">
                                {STEPS.map((step, index) => {
                                    const active = step.id === currentStep;
                                    const completed = completedSteps.includes(step.id);

                                    return (
                                        <button
                                            key={step.id}
                                            type="button"
                                            onClick={() => goToStep(step.id)}
                                            disabled={!active && !completed}
                                            className={`group flex w-full items-center gap-3 rounded-xl border px-3 py-3 text-left text-sm transition ${
                                                active ? 'border-cyan-400 bg-cyan-500/10 text-white' : completed ? 'border-slate-700 bg-slate-950/80 text-slate-200' : 'cursor-not-allowed border-slate-800 bg-slate-950/70 text-slate-500'
                                            }`}
                                        >
                                            <div className={`flex h-8 w-8 flex-none items-center justify-center rounded-xl text-xs font-semibold ${
                                                active ? 'bg-cyan-400 text-slate-950' : completed ? 'bg-emerald-500 text-white' : 'bg-slate-800 text-slate-500'
                                            }`}>
                                                {completed ? <CheckCircle2 className="h-3 w-3" /> : index + 1}
                                            </div>
                                            <div className="min-w-0">
                                                <p className="font-semibold truncate">{step.title}</p>
                                                <p className="text-xs text-slate-500 truncate">{step.description}</p>
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>

                            <div className="mt-6 rounded-2xl bg-slate-950/80 p-4 ring-1 ring-slate-800">
                                <p className="text-xs font-semibold text-slate-300">Why we ask for this</p>
                                <p className="mt-2 text-xs leading-5 text-slate-400">
                                    The information you provide helps us verify your identity, keep your account secure, and comply with banking regulations. Only required fields are requested up front.
                                </p>
                            </div>
                        </section>

                        <Card className="overflow-hidden border border-slate-800 bg-slate-950/95 shadow-2xl shadow-slate-950/30">
                            <CardHeader className="border-b border-slate-800 bg-slate-900/95 px-5 py-4">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="text-lg text-white">{stepData.title}</CardTitle>
                                    <div className="rounded-full border border-slate-700 bg-slate-900 px-3 py-1 text-xs uppercase tracking-[0.24em] text-slate-500">
                                        Step {currentStepIndex + 1}/{STEPS.length}
                                    </div>
                                </div>
                            </CardHeader>

                            <CardContent className="space-y-5 px-5 py-6">
                                <div className="flex items-center gap-4">
                                    <Progress value={stepProgress} className="flex-1 h-1.5 rounded-full bg-slate-800" />
                                    <p className="text-xs text-slate-500 whitespace-nowrap">{stepProgress}%</p>
                                </div>

                                {serverError && (
                                    <Alert className="bg-rose-500/10 border-rose-500/20">
                                        <AlertCircle className="h-4 w-4 text-rose-500" />
                                        <AlertDescription className="text-rose-100 ml-2">{serverError}</AlertDescription>
                                    </Alert>
                                )}

                                <form
                                    action={store.url()}
                                    method="post"
                                    onSubmit={(e) => {
                                        e.preventDefault();

                                        if (isLastStep) {
                                            form.post(store.url());
                                        } else {
                                            handleNext(form.data as Record<string, string>);
                                        }
                                    }}
                                    className="space-y-6"
                                >
                                    {currentStep === 'email' && (
                                        <div className="space-y-6">
                                            <div className="space-y-2">
                                                <Label htmlFor="email" className="text-slate-200">Email address</Label>
                                                <Input
                                                    id="email"
                                                    type="email"
                                                    required
                                                    autoFocus
                                                    autoComplete="email"
                                                    name="email"
                                                    placeholder="email@example.com"
                                                    value={form.data.email}
                                                    onChange={e => form.setData('email', e.target.value)}
                                                    className="bg-slate-800 border-slate-700 text-white"
                                                />
                                                <InputError message={clientErrors.email || form.errors.email} />
                                            </div>
                                        </div>
                                    )}

                                    {currentStep === 'password' && (
                                        <div className="space-y-6">
                                            <div className="space-y-2">
                                                <Label htmlFor="password" className="text-slate-200">Password</Label>
                                                <PasswordInput
                                                    id="password"
                                                    required
                                                    autoComplete="new-password"
                                                    name="password"
                                                    placeholder="Password"
                                                    passwordrules={passwordRules}
                                                    value={form.data.password}
                                                    onChange={e => form.setData('password', e.target.value)}
                                                    className="bg-slate-800 border-slate-700 text-white"
                                                />
                                                <InputError message={clientErrors.password || form.errors.password} />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="password_confirmation" className="text-slate-200">Confirm password</Label>
                                                <PasswordInput
                                                    id="password_confirmation"
                                                    required
                                                    autoComplete="new-password"
                                                    name="password_confirmation"
                                                    placeholder="Confirm password"
                                                    passwordrules={passwordRules}
                                                    value={form.data.password_confirmation}
                                                    onChange={e => form.setData('password_confirmation', e.target.value)}
                                                    className="bg-slate-800 border-slate-700 text-white"
                                                />
                                                <InputError message={clientErrors.password_confirmation} />
                                            </div>
                                        </div>
                                    )}

                                    {currentStep === 'profile' && (
                                        <div className="space-y-6">
                                            <div className="grid gap-4 sm:grid-cols-2">
                                                <div className="space-y-2">
                                                    <Label htmlFor="name" className="text-slate-200">Full name</Label>
                                                    <Input
                                                        id="name"
                                                        type="text"
                                                        required
                                                        autoComplete="name"
                                                        name="name"
                                                        placeholder="John Doe"
                                                        value={form.data.name}
                                                        onChange={e => form.setData('name', e.target.value)}
                                                        className="bg-slate-800 border-slate-700 text-white"
                                                    />
                                                    <InputError message={clientErrors.name} />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="phone_number" className="text-slate-200">Phone number</Label>
                                                    <Input
                                                        id="phone_number"
                                                        type="tel"
                                                        required
                                                        autoComplete="tel"
                                                        name="phone_number"
                                                        placeholder="+1 (555) 000-0000"
                                                        value={form.data.phone_number}
                                                        onChange={e => form.setData('phone_number', e.target.value)}
                                                        className="bg-slate-800 border-slate-700 text-white"
                                                    />
                                                    <InputError message={clientErrors.phone_number} />
                                                </div>
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="date_of_birth" className="text-slate-200">Date of birth</Label>
                                                <Input
                                                    id="date_of_birth"
                                                    type="date"
                                                    required
                                                    name="date_of_birth"
                                                    value={form.data.date_of_birth}
                                                    onChange={e => form.setData('date_of_birth', e.target.value)}
                                                    className="bg-slate-800 border-slate-700 text-white"
                                                />
                                                <InputError message={clientErrors.date_of_birth} />
                                            </div>
                                        </div>
                                    )}

                                    {currentStep === 'kyc' && (
                                        <div className="space-y-6">
                                            <Alert className="bg-blue-500/10 border-blue-500/20">
                                                <AlertCircle className="h-4 w-4 text-blue-500" />
                                                <AlertDescription className="text-blue-200 ml-2">
                                                    We need a few extra details to verify your identity.
                                                </AlertDescription>
                                            </Alert>

                                            <div className="grid gap-4 md:grid-cols-2">
                                                <div className="space-y-2">
                                                    <Label htmlFor="country" className="text-slate-200">Country *</Label>
                                                    <Input
                                                        id="country"
                                                        type="text"
                                                        required
                                                        name="country"
                                                        placeholder="e.g. US"
                                                        maxLength={2}
                                                        value={form.data.country}
                                                        onChange={e => form.setData('country', e.target.value)}
                                                        className="bg-slate-700 border-slate-600 text-white"
                                                    />
                                                    <InputError message={clientErrors.country} />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="id_type" className="text-slate-200">ID Type *</Label>
                                                    <select
                                                        id="id_type"
                                                        name="id_type"
                                                        required
                                                        value={form.data.id_type}
                                                        onChange={e => form.setData('id_type', e.target.value)}
                                                        className="w-full rounded-md border border-slate-700 bg-slate-700 px-3 py-2 text-white"
                                                    >
                                                        <option value="">Select ID type</option>
                                                        <option value="passport">Passport</option>
                                                        <option value="national_id">National ID</option>
                                                        <option value="drivers_license">Driver's License</option>
                                                    </select>
                                                    <InputError message={clientErrors.id_type} />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="id_number" className="text-slate-200">ID Number *</Label>
                                                    <Input
                                                        id="id_number"
                                                        type="text"
                                                        required
                                                        name="id_number"
                                                        placeholder="ID Number"
                                                        value={form.data.id_number}
                                                        onChange={e => form.setData('id_number', e.target.value)}
                                                        className="bg-slate-700 border-slate-600 text-white"
                                                    />
                                                    <InputError message={clientErrors.id_number} />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="postal_code" className="text-slate-200">Postal Code *</Label>
                                                    <Input
                                                        id="postal_code"
                                                        type="text"
                                                        required
                                                        name="postal_code"
                                                        placeholder="12345"
                                                        value={form.data.postal_code}
                                                        onChange={e => form.setData('postal_code', e.target.value)}
                                                        className="bg-slate-700 border-slate-600 text-white"
                                                    />
                                                    <InputError message={clientErrors.postal_code} />
                                                </div>
                                                <div className="md:col-span-2 space-y-2">
                                                    <Label htmlFor="address_line1" className="text-slate-200">Address Line 1 *</Label>
                                                    <Input
                                                        id="address_line1"
                                                        type="text"
                                                        required
                                                        name="address_line1"
                                                        placeholder="Street address"
                                                        value={form.data.address_line1}
                                                        onChange={e => form.setData('address_line1', e.target.value)}
                                                        className="bg-slate-700 border-slate-600 text-white"
                                                    />
                                                    <InputError message={clientErrors.address_line1} />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="city" className="text-slate-200">City *</Label>
                                                    <Input
                                                        id="city"
                                                        type="text"
                                                        required
                                                        name="city"
                                                        placeholder="City"
                                                        value={form.data.city}
                                                        onChange={e => form.setData('city', e.target.value)}
                                                        className="bg-slate-700 border-slate-600 text-white"
                                                    />
                                                    <InputError message={clientErrors.city} />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="state" className="text-slate-200">State / Province</Label>
                                                    <Input
                                                        id="state"
                                                        type="text"
                                                        name="state"
                                                        placeholder="State"
                                                        value={form.data.state}
                                                        onChange={e => form.setData('state', e.target.value)}
                                                        className="bg-slate-700 border-slate-600 text-white"
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="source_of_funds" className="text-slate-200">Source of funds</Label>
                                                    <select
                                                        id="source_of_funds"
                                                        name="source_of_funds"
                                                        value={form.data.source_of_funds}
                                                        onChange={e => form.setData('source_of_funds', e.target.value)}
                                                        className="w-full rounded-md border border-slate-700 bg-slate-700 px-3 py-2 text-white"
                                                    >
                                                        <option value="">Select source</option>
                                                        <option value="employment">Employment Income</option>
                                                        <option value="business">Business Income</option>
                                                        <option value="investment">Investment</option>
                                                        <option value="inheritance">Inheritance</option>
                                                        <option value="other">Other</option>
                                                    </select>
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="occupation" className="text-slate-200">Occupation</Label>
                                                    <Input
                                                        id="occupation"
                                                        type="text"
                                                        name="occupation"
                                                        placeholder="Your occupation"
                                                        value={form.data.occupation}
                                                        onChange={e => form.setData('occupation', e.target.value)}
                                                        className="bg-slate-700 border-slate-600 text-white"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {currentStep === 'confirmation' && (
                                        <div className="space-y-6 rounded-3xl border border-slate-800 bg-slate-900/80 p-5">
                                            <Alert className="bg-emerald-500/10 border-emerald-500/20">
                                                <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                                                <AlertDescription className="text-emerald-200 ml-2">
                                                    Review your details before submitting your account request.
                                                </AlertDescription>
                                            </Alert>

                                            <div className="grid gap-6 md:grid-cols-2">
                                                <div className="space-y-4">
                                                    <h3 className="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Account information</h3>
                                                    <div className="space-y-2 text-sm text-slate-300">
                                                        <div className="flex justify-between border-b border-slate-800 pb-2">
                                                            <span className="text-slate-500">Email</span>
                                                            <span>{form.data.email || '—'}</span>
                                                        </div>
                                                        <div className="flex justify-between border-b border-slate-800 pb-2">
                                                            <span className="text-slate-500">Name</span>
                                                            <span>{form.data.name || '—'}</span>
                                                        </div>
                                                        <div className="flex justify-between">
                                                            <span className="text-slate-500">Phone</span>
                                                            <span>{form.data.phone_number || '—'}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="space-y-4">
                                                    <h3 className="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Identity details</h3>
                                                    <div className="space-y-2 text-sm text-slate-300">
                                                        <div className="flex justify-between border-b border-slate-800 pb-2">
                                                            <span className="text-slate-500">Country</span>
                                                            <span>{form.data.country || '—'}</span>
                                                        </div>
                                                        <div className="flex justify-between border-b border-slate-800 pb-2">
                                                            <span className="text-slate-500">ID type</span>
                                                            <span>{form.data.id_type ? form.data.id_type.replace('_', ' ') : '—'}</span>
                                                        </div>
                                                        <div className="flex justify-between">
                                                            <span className="text-slate-500">City / State</span>
                                                            <span>{form.data.city || form.data.state ? `${form.data.city}${form.data.state ? `, ${form.data.state}` : ''}` : '—'}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <Alert className="bg-amber-500/10 border-amber-500/20">
                                                <AlertCircle className="h-4 w-4 text-amber-500" />
                                                <AlertDescription className="text-amber-200 ml-2">
                                                    By creating an account, you agree to our Terms of Service and Privacy Policy.
                                                </AlertDescription>
                                            </Alert>
                                        </div>
                                    )}

                                    <div className="flex flex-col gap-4 border-t border-slate-700 pt-6 sm:flex-row">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={handlePrevious}
                                            disabled={isFirstStep || form.processing}
                                            className="flex-1"
                                        >
                                            <ChevronLeft className="w-4 h-4 mr-2" />
                                            Previous
                                        </Button>
                                        {!isLastStep ? (
                                            <Button
                                                type="button"
                                                onClick={() => handleNext(form.data as Record<string, string>)}
                                                disabled={form.processing}
                                                className="flex-1"
                                            >
                                                {form.processing && <Spinner />}
                                                Next
                                                <ChevronRight className="w-4 h-4 ml-2" />
                                            </Button>
                                        ) : (
                                            <Button
                                                type="submit"
                                                disabled={form.processing}
                                                className="flex-1 bg-emerald-600 hover:bg-emerald-700"
                                            >
                                                {form.processing && <Spinner />}
                                                Create account
                                            </Button>
                                        )}
                                    </div>
                                </form>

                                <div className="text-center text-sm text-slate-400">
                                    Already have an account?{' '}
                                    <TextLink href={login()} className="text-cyan-300 hover:text-cyan-200">
                                        Sign in
                                    </TextLink>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}
