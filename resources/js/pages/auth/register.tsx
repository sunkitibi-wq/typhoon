import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';
import { CheckCircle2, Circle, ChevronRight, ChevronLeft, AlertCircle } from 'lucide-react';
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
            case 'email':
                if (!data.email) newErrors.email = 'Email is required';
                else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) newErrors.email = 'Invalid email format';
                break;
            case 'password':
                if (!data.password) newErrors.password = 'Password is required';
                else if (data.password.length < 8) newErrors.password = 'Password must be at least 8 characters';
                if (data.password !== data.password_confirmation)
                    newErrors.password_confirmation = 'Passwords do not match';
                break;
            case 'profile':
                if (!data.name) newErrors.name = 'Name is required';
                if (!data.phone_number) newErrors.phone_number = 'Phone number is required';
                if (!data.date_of_birth) newErrors.date_of_birth = 'Date of birth is required';
                break;
            case 'kyc':
                if (!data.country) newErrors.country = 'Country is required';
                if (!data.id_type) newErrors.id_type = 'ID type is required';
                if (!data.id_number) newErrors.id_number = 'ID number is required';
                if (!data.address_line1) newErrors.address_line1 = 'Address is required';
                if (!data.city) newErrors.city = 'City is required';
                if (!data.postal_code) newErrors.postal_code = 'Postal code is required';
                break;
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

    return (
        <>
            <Head title="Register" />
            <div className="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex items-center justify-center p-4">
                <div className="w-full max-w-4xl mx-auto">
                    <div className="grid md:grid-cols-[300px_1fr] gap-8">
                        <div className="hidden md:block">
                            <Card className="bg-slate-800 border-slate-700 shadow-2xl shadow-slate-950/40 sticky top-8">
                                <CardHeader className="pb-4">
                                    <CardTitle className="text-lg text-white">Account Setup</CardTitle>
                                    <CardDescription className="text-slate-400">Step {currentStepIndex + 1} of {STEPS.length}</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {STEPS.map((step) => (
                                        <button
                                            key={step.id}
                                            onClick={() => goToStep(step.id)}
                                            disabled={!completedSteps.includes(step.id) && step.id !== currentStep}
                                            className={`w-full text-left flex items-start gap-3 p-2 rounded-lg transition-colors ${
                                                currentStep === step.id
                                                    ? 'bg-blue-500/20 border border-blue-400'
                                                    : completedSteps.includes(step.id)
                                                    ? 'hover:bg-slate-700 cursor-pointer'
                                                    : 'opacity-50 cursor-not-allowed'
                                            }`}
                                        >
                                            <div className="flex-shrink-0 mt-1">
                                                {completedSteps.includes(step.id) ? (
                                                    <CheckCircle2 className="w-5 h-5 text-emerald-500" />
                                                ) : (
                                                    <Circle className={`w-5 h-5 ${currentStep === step.id ? 'text-blue-400' : 'text-slate-500'}`} />
                                                )}
                                            </div>
                                            <div>
                                                <p className={`font-medium text-sm ${currentStep === step.id ? 'text-blue-400' : 'text-slate-200'}`}>
                                                    {step.title}
                                                </p>
                                                <p className="text-xs text-slate-500">{step.description}</p>
                                            </div>
                                        </button>
                                    ))}
                                </CardContent>
                            </Card>
                        </div>

                        <Card className="bg-slate-800 border-slate-700 shadow-2xl shadow-slate-950/40">
                            <CardHeader>
                                <CardTitle className="text-2xl text-white">{stepData.title}</CardTitle>
                                <CardDescription className="text-slate-400">{stepData.description}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-8">
                                <div className="space-y-4">
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p className="text-sm uppercase tracking-[0.3em] text-slate-500">Step {currentStepIndex + 1} of {STEPS.length}</p>
                                            <p className="text-lg font-semibold text-white">{stepData.title}</p>
                                        </div>
                                        <div className="hidden md:flex items-center gap-2">
                                            {STEPS.map((step, index) => (
                                                <div key={step.id} className="flex items-center gap-2">
                                                    <div className={`flex h-8 w-8 items-center justify-center rounded-full border ${
                                                        currentStepIndex === index
                                                            ? 'border-blue-400 bg-blue-500 text-white'
                                                            : completedSteps.includes(step.id)
                                                            ? 'border-emerald-500 bg-emerald-500/10 text-emerald-400'
                                                            : 'border-slate-700 bg-slate-800 text-slate-500'
                                                    }`}>
                                                        {completedSteps.includes(step.id) ? (
                                                            <CheckCircle2 className="h-4 w-4" />
                                                        ) : (
                                                            <span className="text-xs font-semibold">{index + 1}</span>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                    <div className="space-y-3">
                                        <Progress value={stepProgress} className="h-2" />
                                        <div className="flex items-center justify-between text-sm text-slate-400">
                                            <span>{STEPS[currentStepIndex].title}</span>
                                            <span>{stepProgress}% complete</span>
                                        </div>
                                        <div className="flex flex-wrap gap-2 md:hidden">
                                            {STEPS.map((step, index) => (
                                                <span
                                                    key={step.id}
                                                    className={`rounded-full border px-3 py-1 text-xs font-semibold ${
                                                        currentStepIndex === index
                                                            ? 'border-blue-400 bg-blue-500 text-white'
                                                            : completedSteps.includes(step.id)
                                                            ? 'border-emerald-500 bg-emerald-500/10 text-emerald-300'
                                                            : 'border-slate-700 bg-slate-900 text-slate-400'
                                                    }`}
                                                >
                                                    {step.title}
                                                </span>
                                            ))}
                                        </div>
                                    </div>
                                </div>
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
                                    className="space-y-6 rounded-3xl border border-slate-700 bg-slate-950/50 p-6 shadow-lg shadow-slate-950/20"
                                >
                                            {currentStep === 'email' && (
                                                <div className="space-y-4">
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
                                                            className="bg-slate-700 border-slate-600 text-white"
                                                        />
                                                        <InputError message={clientErrors.email || form.errors.email} />
                                                    </div>
                                                </div>
                                            )}

                                            {currentStep === 'password' && (
                                                <div className="space-y-4">
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
                                                            className="bg-slate-700 border-slate-600 text-white"
                                                        />
                                                        <InputError message={clientErrors.password} />
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
                                                            className="bg-slate-700 border-slate-600 text-white"
                                                        />
                                                        <InputError message={clientErrors.password_confirmation} />
                                                    </div>
                                                </div>
                                            )}

                                            {currentStep === 'profile' && (
                                                <div className="space-y-4">
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
                                                            className="bg-slate-700 border-slate-600 text-white"
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
                                                            className="bg-slate-700 border-slate-600 text-white"
                                                        />
                                                        <InputError message={clientErrors.phone_number} />
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
                                                            className="bg-slate-700 border-slate-600 text-white"
                                                        />
                                                        <InputError message={clientErrors.date_of_birth} />
                                                    </div>
                                                </div>
                                            )}

                                            {currentStep === 'kyc' && (
                                                <div className="space-y-4 max-h-[500px] overflow-y-auto">
                                                    <Alert className="bg-blue-500/10 border-blue-500/20">
                                                        <AlertCircle className="h-4 w-4 text-blue-500" />
                                                        <AlertDescription className="text-blue-200 ml-2">
                                                            We need to verify your identity to comply with regulations
                                                        </AlertDescription>
                                                    </Alert>
                                                    <div className="grid md:grid-cols-2 gap-4">
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
                                                                className="w-full px-3 py-2 bg-slate-700 border border-slate-600 text-white rounded-md"
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
                                                            <Label htmlFor="state" className="text-slate-200">State/Province</Label>
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
                                                            <Label htmlFor="source_of_funds" className="text-slate-200">Source of Funds</Label>
                                                            <select
                                                                id="source_of_funds"
                                                                name="source_of_funds"
                                                                value={form.data.source_of_funds}
                                                                onChange={e => form.setData('source_of_funds', e.target.value)}
                                                                className="w-full px-3 py-2 bg-slate-700 border border-slate-600 text-white rounded-md"
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
                                                <div className="space-y-6">
                                                    <Alert className="bg-emerald-500/10 border-emerald-500/20">
                                                        <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                                                        <AlertDescription className="text-emerald-200 ml-2">
                                                            Please review your information before submitting
                                                        </AlertDescription>
                                                    </Alert>

                                                    <div className="grid md:grid-cols-2 gap-6">
                                                        <div className="space-y-3">
                                                            <h3 className="font-semibold text-slate-200 text-sm uppercase tracking-wide">Account Information</h3>
                                                            <div className="space-y-2 text-sm">
                                                                <div className="flex justify-between">
                                                                    <span className="text-slate-400">Email:</span>
                                                                    <span className="text-slate-200">{form.data.email}</span>
                                                                </div>
                                                                <div className="flex justify-between">
                                                                    <span className="text-slate-400">Name:</span>
                                                                    <span className="text-slate-200">{form.data.name}</span>
                                                                </div>
                                                                <div className="flex justify-between">
                                                                    <span className="text-slate-400">Phone:</span>
                                                                    <span className="text-slate-200">{form.data.phone_number}</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div className="space-y-3">
                                                            <h3 className="font-semibold text-slate-200 text-sm uppercase tracking-wide">Identity Information</h3>
                                                            <div className="space-y-2 text-sm">
                                                                <div className="flex justify-between">
                                                                    <span className="text-slate-400">Country:</span>
                                                                    <span className="text-slate-200">{form.data.country}</span>
                                                                </div>
                                                                <div className="flex justify-between">
                                                                    <span className="text-slate-400">ID Type:</span>
                                                                    <span className="text-slate-200 capitalize">{form.data.id_type?.replace('_', ' ')}</span>
                                                                </div>
                                                                <div className="flex justify-between">
                                                                    <span className="text-slate-400">Address:</span>
                                                                    <span className="text-slate-200">{form.data.city}, {form.data.state}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <Alert className="bg-amber-500/10 border-amber-500/20">
                                                        <AlertCircle className="h-4 w-4 text-amber-500" />
                                                        <AlertDescription className="text-amber-200 ml-2">
                                                            By creating an account, you agree to our Terms of Service and Privacy Policy
                                                        </AlertDescription>
                                                    </Alert>
                                                </div>
                                            )}

                                            <div className="flex gap-4 pt-6 border-t border-slate-700">
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
                                                        Create Account
                                                    </Button>
                                                )}
                                            </div>
                                </form>

                                <div className="mt-6 text-center text-sm text-slate-400">
                                    Already have an account?{' '}
                                    <TextLink href={login()} className="text-blue-400 hover:text-blue-300">
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
