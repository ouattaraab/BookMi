'use client';

import { useState, useRef, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { messageApi } from '@/lib/api/endpoints';
import { useAuthStore } from '@/lib/store/auth';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert } from '@/components/ui/alert';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Send, MessageSquare } from 'lucide-react';
import { cn } from '@/lib/utils';

type Conversation = {
  id: number;
  other_user?: {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
  };
  last_message?: {
    content: string;
    created_at: string;
  };
  unread_count?: number;
};

type Message = {
  id: number;
  content: string;
  sender_id: number;
  created_at: string;
};

function formatTime(dateStr: string): string {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  const now = new Date();
  if (d.toDateString() === now.toDateString()) {
    return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  }
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
}

function getInitials(firstName?: string, lastName?: string): string {
  return `${firstName?.[0] ?? ''}${lastName?.[0] ?? ''}`.toUpperCase() || '?';
}

export default function ManagerMessagesPage() {
  const queryClient = useQueryClient();
  const currentUser = useAuthStore((s) => s.user);
  const [selectedConvId, setSelectedConvId] = useState<number | null>(null);
  const [messageText, setMessageText] = useState('');
  const [sendError, setSendError] = useState<string | null>(null);
  const messagesEndRef = useRef<HTMLDivElement>(null);

  const { data: convData, isLoading: loadingConvs } = useQuery({
    queryKey: ['conversations'],
    queryFn: () => messageApi.listConversations(),
    refetchInterval: 15000,
  });

  const conversations: Conversation[] = convData?.data?.data ?? [];

  const { data: msgData, isLoading: loadingMessages } = useQuery({
    queryKey: ['messages', selectedConvId],
    queryFn: () => messageApi.getMessages(selectedConvId!),
    enabled: selectedConvId !== null,
    refetchInterval: 5000,
  });

  const messages: Message[] = msgData?.data?.data ?? [];

  const sendMutation = useMutation({
    mutationFn: (content: string) =>
      messageApi.sendMessage(selectedConvId!, content),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['messages', selectedConvId] });
      queryClient.invalidateQueries({ queryKey: ['conversations'] });
      setMessageText('');
      setSendError(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setSendError(e?.response?.data?.message ?? 'Erreur lors de l\'envoi');
    },
  });

  const handleSend = () => {
    const text = messageText.trim();
    if (!text || !selectedConvId) return;
    setSendError(null);
    sendMutation.mutate(text);
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  };

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  const selectedConv = conversations.find((c) => c.id === selectedConvId);

  return (
    <div className="space-y-4 h-full">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Messages</h1>
        <p className="text-gray-500 text-sm mt-1">
          Toutes vos conversations
        </p>
      </div>

      <Card className="overflow-hidden" style={{ height: 'calc(100vh - 220px)' }}>
        <div className="flex h-full">
          {/* Conversations sidebar */}
          <div className="w-80 border-r border-gray-200 flex flex-col">
            <div className="p-4 bg-gray-50 border-b border-gray-200">
              <p className="text-sm font-semibold text-gray-700">Conversations</p>
            </div>
            <div className="flex-1 overflow-y-auto">
              {loadingConvs ? (
                <div className="p-4 space-y-3">
                  {[...Array(5)].map((_, i) => (
                    <Skeleton key={i} className="h-16 w-full" />
                  ))}
                </div>
              ) : conversations.length === 0 ? (
                <div className="p-8 text-center">
                  <MessageSquare size={32} className="text-gray-300 mx-auto mb-3" />
                  <p className="text-gray-400 text-sm">Aucune conversation</p>
                </div>
              ) : (
                conversations.map((conv) => {
                  const other = conv.other_user;
                  const isSelected = conv.id === selectedConvId;
                  return (
                    <button
                      key={conv.id}
                      onClick={() => setSelectedConvId(conv.id)}
                      className={cn(
                        'w-full text-left p-4 flex items-center gap-3 hover:bg-gray-50 transition-colors border-b border-gray-100',
                        isSelected && 'bg-amber-50 border-l-2 border-l-amber-500'
                      )}
                    >
                      <Avatar className="h-10 w-10 shrink-0">
                        <AvatarFallback className="bg-gray-200 text-gray-600 text-xs">
                          {getInitials(other?.first_name, other?.last_name)}
                        </AvatarFallback>
                      </Avatar>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center justify-between">
                          <p className="text-sm font-medium text-gray-800 truncate">
                            {other
                              ? `${other.first_name} ${other.last_name}`
                              : 'Inconnu'}
                          </p>
                          {conv.last_message && (
                            <span className="text-xs text-gray-400 shrink-0 ml-2">
                              {formatTime(conv.last_message.created_at)}
                            </span>
                          )}
                        </div>
                        <p className="text-xs text-gray-500 truncate mt-0.5">
                          {conv.last_message?.content ?? other?.email ?? ''}
                        </p>
                      </div>
                      {conv.unread_count && conv.unread_count > 0 ? (
                        <span className="shrink-0 w-5 h-5 bg-amber-500 text-white rounded-full text-xs flex items-center justify-center">
                          {conv.unread_count}
                        </span>
                      ) : null}
                    </button>
                  );
                })
              )}
            </div>
          </div>

          {/* Messages area */}
          <div className="flex-1 flex flex-col">
            {selectedConvId === null ? (
              <div className="flex-1 flex items-center justify-center">
                <div className="text-center">
                  <MessageSquare size={48} className="text-gray-200 mx-auto mb-4" />
                  <p className="text-gray-400">Sélectionnez une conversation</p>
                </div>
              </div>
            ) : (
              <>
                <div className="px-6 py-4 bg-white border-b border-gray-200 flex items-center gap-3">
                  <Avatar className="h-9 w-9">
                    <AvatarFallback className="bg-amber-100 text-amber-700 text-xs">
                      {getInitials(
                        selectedConv?.other_user?.first_name,
                        selectedConv?.other_user?.last_name
                      )}
                    </AvatarFallback>
                  </Avatar>
                  <div>
                    <p className="text-sm font-semibold text-gray-800">
                      {selectedConv?.other_user
                        ? `${selectedConv.other_user.first_name} ${selectedConv.other_user.last_name}`
                        : 'Inconnu'}
                    </p>
                    <p className="text-xs text-gray-400">
                      {selectedConv?.other_user?.email}
                    </p>
                  </div>
                </div>

                <div className="flex-1 overflow-y-auto p-6 space-y-4">
                  {loadingMessages ? (
                    <div className="space-y-3">
                      {[...Array(4)].map((_, i) => (
                        <Skeleton key={i} className="h-10 w-2/3" />
                      ))}
                    </div>
                  ) : messages.length === 0 ? (
                    <div className="text-center text-gray-400 text-sm pt-8">
                      Aucun message. Commencez la conversation !
                    </div>
                  ) : (
                    messages.map((msg) => {
                      const isMe = msg.sender_id === currentUser?.id;
                      return (
                        <div
                          key={msg.id}
                          className={cn('flex', isMe ? 'justify-end' : 'justify-start')}
                        >
                          <div
                            className={cn(
                              'max-w-[70%] px-4 py-2.5 rounded-2xl text-sm',
                              isMe
                                ? 'bg-amber-500 text-white rounded-br-sm'
                                : 'bg-gray-100 text-gray-800 rounded-bl-sm'
                            )}
                          >
                            <p className="leading-relaxed">{msg.content}</p>
                            <p className={cn('text-xs mt-1', isMe ? 'text-amber-100' : 'text-gray-400')}>
                              {formatTime(msg.created_at)}
                            </p>
                          </div>
                        </div>
                      );
                    })
                  )}
                  <div ref={messagesEndRef} />
                </div>

                <div className="px-6 py-4 bg-white border-t border-gray-200">
                  {sendError && (
                    <Alert className="bg-red-50 border-red-200 text-red-800 text-xs mb-3">
                      {sendError}
                    </Alert>
                  )}
                  <div className="flex gap-3">
                    <Input
                      value={messageText}
                      onChange={(e) => setMessageText(e.target.value)}
                      onKeyDown={handleKeyDown}
                      placeholder="Écrire un message... (Entrée pour envoyer)"
                      className="flex-1"
                    />
                    <Button
                      onClick={handleSend}
                      disabled={!messageText.trim() || sendMutation.isPending}
                      className="bg-amber-500 hover:bg-amber-600 text-white px-4"
                    >
                      <Send size={16} />
                    </Button>
                  </div>
                </div>
              </>
            )}
          </div>
        </div>
      </Card>
    </div>
  );
}
