import { Form, Head } from '@inertiajs/react';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Mail, CheckCircle2, AlertCircle, Send } from 'lucide-react';
import { logout } from '@/routes';
import { send } from '@/routes/verification';
import { useState } from 'react';

export default function VerifyEmail({ status }: { status?: string }) {
    const [resendClicked, setResendClicked] = useState(false);

    return (
        <>
            <Head title="Verify your email" />
            <div className="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex items-center justify-center p-4">
                <div className="w-full max-w-md">
                    <Card className="bg-slate-800 border-slate-700">
                        <CardHeader className="text-center">
                            <div className="flex justify-center mb-4">
                                <div className="bg-blue-500/20 p-4 rounded-full">
                                    <Mail className="w-8 h-8 text-blue-400" />
                                </div>
                            </div>
                            <CardTitle className="text-2xl text-white">Verify Your Email</CardTitle>
                            <CardDescription className="text-slate-400 mt-2">
                                We've sent a verification link to your email address
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {status === 'verification-link-sent' && (
                                <Alert className="bg-emerald-500/10 border-emerald-500/20">
                                    <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                                    <AlertDescription className="text-emerald-200 ml-2">
                                        A new verification link has been sent to your email address.
                                    </AlertDescription>
                                </Alert>
                            )}

                            <div className="space-y-4 text-center">
                                <p className="text-slate-300">
                                    Check your inbox and click the verification link to confirm your email address. The link expires in 24 hours.
                                </p>
                                
                                <div className="bg-slate-700/50 border border-slate-600 rounded-lg p-4">
                                    <p className="text-sm text-slate-400 mb-2">Didn't receive the email?</p>
                                    <ul className="text-sm text-slate-300 space-y-1 text-left">
                                        <li>• Check your spam or junk folder</li>
                                        <li>• Make sure you entered the correct email</li>
                                        <li>• Try requesting a new verification link</li>
                                    </ul>
                                </div>
                            </div>

                            <Form {...send.form()} className="space-y-4">
                                {({ processing }) => (
                                    <>
                                        <Button 
                                            type="submit"
                                            disabled={processing} 
                                            className="w-full bg-blue-600 hover:bg-blue-700"
                                            onClick={() => setResendClicked(true)}
                                        >
                                            {processing && <Spinner />}
                                            <Send className="w-4 h-4 mr-2" />
                                            {resendClicked ? 'Sending...' : 'Resend Verification Link'}
                                        </Button>
                                    </>
                                )}
                            </Form>

                            <div className="border-t border-slate-600 pt-4">
                                <TextLink
                                    href={logout()}
                                    className="text-slate-400 hover:text-slate-300 text-center block"
                                >
                                    Sign out and try a different email
                                </TextLink>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="mt-6 text-center text-sm text-slate-400">
                        <p>
                            After verification, you can complete your KYC and start using all features.
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
