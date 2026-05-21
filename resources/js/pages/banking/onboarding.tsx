import { Head, Link } from '@inertiajs/react';
import { CheckCircle, Circle, ArrowRight, User, ShieldCheck, CreditCard, ArrowDownToLine } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

interface OnboardingData {
    profile_complete: boolean;
    kyc_submitted: boolean;
    kyc_approved: boolean;
    has_account: boolean;
    has_deposit: boolean;
    current_step: number;
    steps: Array<{ key: string; label: string; completed: boolean; active: boolean; href: string }>;
}

export default function Onboarding({ onboarding }: { onboarding: OnboardingData }) {
    return (
        <>
            <Head title="Getting Started" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-2xl font-bold">Getting Started</h1>
                <p className="text-muted-foreground">Complete these steps to start using your Typhoon Banking account.</p>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader><CardTitle>Your Onboarding Progress</CardTitle></CardHeader>
                        <CardContent className="space-y-0">
                            {onboarding.steps.map((step, idx) => (
                                <div key={step.key} className="relative flex gap-4 pb-8 last:pb-0">
                                    {idx < onboarding.steps.length - 1 && (
                                        <div className="absolute left-4 top-10 h-full w-px bg-border" />
                                    )}
                                    <div className="flex size-8 shrink-0 items-center justify-center rounded-full border bg-background">
                                        {step.completed ? (
                                            <CheckCircle className="size-5 text-emerald-500" />
                                        ) : step.active ? (
                                            <Circle className="size-5 text-blue-500" />
                                        ) : (
                                            <Circle className="size-5 text-muted-foreground" />
                                        )}
                                    </div>
                                    <div className="flex-1 pt-1">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <p className={`font-medium ${step.active ? 'text-foreground' : step.completed ? 'text-emerald-600' : 'text-muted-foreground'}`}>
                                                    {step.label}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {step.completed ? 'Completed' : step.active ? 'In progress' : 'Pending'}
                                                </p>
                                            </div>
                                            {step.active && (
                                                <Button size="sm" asChild>
                                                    <Link href={step.href}>Continue <ArrowRight className="ml-1 size-3" /></Link>
                                                </Button>
                                            )}
                                            {step.completed && (
                                                <Badge variant="default" className="bg-emerald-500">Done</Badge>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>Quick Summary</CardTitle></CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center gap-3 rounded-lg border p-3">
                                <User className="size-5 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium">Profile</p>
                                    <p className="text-xs text-muted-foreground">{onboarding.profile_complete ? 'Complete' : 'Incomplete'}</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-3 rounded-lg border p-3">
                                <ShieldCheck className="size-5 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium">KYC Verification</p>
                                    <p className="text-xs text-muted-foreground">
                                        {onboarding.kyc_approved ? 'Approved' : onboarding.kyc_submitted ? 'Submitted' : 'Not started'}
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-center gap-3 rounded-lg border p-3">
                                <CreditCard className="size-5 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium">Account</p>
                                    <p className="text-xs text-muted-foreground">{onboarding.has_account ? 'Opened' : 'Not opened'}</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-3 rounded-lg border p-3">
                                <ArrowDownToLine className="size-5 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium">First Deposit</p>
                                    <p className="text-xs text-muted-foreground">{onboarding.has_deposit ? 'Deposited' : 'No deposit'}</p>
                                </div>
                            </div>

                            {onboarding.current_step <= onboarding.steps.length && (
                                <Button className="w-full" asChild>
                                    <Link href={onboarding.steps.find(s => s.active)?.href || onboarding.steps[0].href}>
                                        {onboarding.current_step > onboarding.steps.length ? 'All Done!' : 'Continue'} <ArrowRight className="ml-1 size-4" />
                                    </Link>
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

Onboarding.layout = {
    breadcrumbs: [
        { title: 'Banking', href: '/banking/dashboard' },
        { title: 'Getting Started', href: '' },
    ],
};
